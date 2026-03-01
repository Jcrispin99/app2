<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\PurchaseRequest;
use App\Http\Resources\PurchaseResource;
use App\Models\Journal;
use App\Models\Partner;
use App\Models\Purchase;
use App\Models\Tax;
use App\Models\UnitOfMeasure;
use App\Models\Warehouse;
use App\Services\KardexService;
use App\Services\SequenceService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Activity;

final class PurchaseController extends Controller
{
    /**
     * @var array<string, array<string, mixed>>
     */
    private const PURCHASE_LOAD_RELATIONS = [
        'partner',
        'warehouse',
        'company',
        'productables.productProduct.template.uom',
        'productables.productProduct.attributeValues',
        'productables.tax',
        'productables.uom',
    ];

    /**
     * Listar Compras
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 25);
        $search = $request->input('search');
        $status = $request->input('status');
        $from = $request->input('from');
        $to = $request->input('to');

        $query = Purchase::query()
            ->with(['partner', 'warehouse', 'company'])
            ->orderBy('created_at', 'desc');

        if ($status) {
            $query->where('status', $status);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('serie', 'like', "%{$search}%")
                    ->orWhere('correlative', 'like', "%{$search}%")
                    ->orWhereHas('partner', function ($partnerQuery) use ($search) {
                        $partnerQuery->where('business_name', 'like', "%{$search}%")
                            ->orWhere('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%");
                    });
            });
        }

        if ($from || $to) {
            $fromDate = $from ? Carbon::parse($from)->startOfDay() : null;
            $toDate = $to ? Carbon::parse($to)->endOfDay() : null;

            if ($fromDate && $toDate) {
                $query->whereBetween('created_at', [$fromDate, $toDate]);
            } elseif ($fromDate) {
                $query->where('created_at', '>=', $fromDate);
            } elseif ($toDate) {
                $query->where('created_at', '<=', $toDate);
            }
        }

        $draftTotal = (clone $query)->where('status', 'draft')->count();
        $postedTotal = (clone $query)->where('status', 'posted')->count();

        $paginator = $query->paginate((int) $perPage)->appends($request->query());

        return response()->json([
            'success' => true,
            'message' => 'Purchases listing retrieved successfully',
            'data' => PurchaseResource::collection($paginator->items()),
            'links' => $paginator->linkCollection(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
                'draft_total' => $draftTotal,
                'posted_total' => $postedTotal,
            ],
        ]);
    }

    /**
     * Opciones de Formulario
     */
    public function formOptions(): JsonResponse
    {
        $suppliers = Partner::query()
            ->suppliers()
            ->orderBy('business_name')
            ->orderBy('first_name')
            ->get();

        $warehouses = Warehouse::query()->latest()->get();

        // Warning: This falls back gracefully if Tax model is not fully implemented/migrated yet
        $taxes = [];
        if (class_exists(Tax::class)) {
            $taxes = Tax::query()
                ->where('is_active', true)
                ->orderByDesc('is_default')
                ->orderBy('name')
                ->get();
        }

        return $this->success([
            'suppliers' => \App\Http\Resources\SupplierResource::collection($suppliers),
            'warehouses' => \App\Http\Resources\WarehouseResource::collection($warehouses),
            'taxes' => $taxes,
        ], 'Form options retrieved successfully');
    }

    /**
     * Ver Compra
     */
    public function show(Purchase $purchase): JsonResponse
    {
        $purchase->load(self::PURCHASE_LOAD_RELATIONS);

        $activities = Activity::forSubject($purchase)
            ->with('causer')
            ->latest()
            ->take(20)
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Record retrieved successfully',
            'data' => new PurchaseResource($purchase),
            'meta' => [
                'activities' => $activities,
            ],
        ]);
    }

    /**
     * Crear Compra
     */
    public function store(PurchaseRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $purchaseId = null;

        $companyId = $validated['company_id'] ?? $request->user()?->company_id;

        if (! $companyId) {
            return $this->error('No se pudo determinar la compañía.', 422);
        }

        DB::transaction(function () use ($validated, &$purchaseId, $companyId) {
            $defaultJournal = Journal::where('type', 'purchase')
                ->where('company_id', $companyId)
                ->first();

            if (! $defaultJournal) {
                // Warning logic, but since it's an API, best approach is throwing standard ValidationException or abort
                throw ValidationException::withMessages([
                    'journal' => 'No se encontró un diario de compras para esta compañía. Por favor crea uno primero.',
                ]);
            }

            // Using dummy sequence numbers if SequenceService isn't fully set up yet
            $serie = 'F001';
            $correlative = '0000001';

            if (class_exists(SequenceService::class)) {
                $numberParts = SequenceService::getNextParts($defaultJournal->id);
                $serie = $numberParts['serie'];
                $correlative = $numberParts['correlative'];
            }

            $purchase = Purchase::create([
                'partner_id' => $validated['partner_id'],
                'warehouse_id' => $validated['warehouse_id'],
                'journal_id' => $defaultJournal->id,
                'company_id' => $companyId,
                'vendor_bill_number' => $validated['vendor_bill_number'] ?? null,
                'vendor_bill_date' => $validated['vendor_bill_date'] ?? null,
                'observation' => $validated['observation'] ?? null,
                'status' => 'draft',
                'payment_status' => 'unpaid',
                'serie' => $serie,
                'correlative' => $correlative,
                'total' => 0,
            ]);

            $total = 0;
            foreach ($validated['products'] as $productData) {
                $line = $this->prepareLineData($productData);
                $tax = isset($line['tax_id']) && class_exists(Tax::class)
                    ? Tax::find($line['tax_id'])
                    : null;

                $quantity = $line['quantity'];
                $price = $line['price'];
                $subtotal = $quantity * $price;

                $taxRate = $tax ? $tax->rate_percent : 0;
                $taxAmount = $subtotal * ($taxRate / 100);
                $lineTotal = $subtotal + $taxAmount;

                $purchase->productables()->create([
                    'product_product_id' => $line['product_product_id'],
                    'quantity' => $quantity,
                    'price' => $price,
                    'subtotal' => $subtotal,
                    'tax_id' => $line['tax_id'],
                    'tax_rate' => $taxRate,
                    'tax_amount' => $taxAmount,
                    'total' => $lineTotal,
                    'uom_id' => $line['uom_id'],
                    'quantity_uom' => $line['quantity_uom'],
                    'price_uom' => $line['price_uom'],
                    'uom_factor' => $line['uom_factor'],
                ]);

                $total += $lineTotal;
            }

            $purchase->update(['total' => $total]);
            $purchaseId = $purchase->id;
        });

        $purchase = Purchase::query()
            ->with(self::PURCHASE_LOAD_RELATIONS)
            ->findOrFail($purchaseId);

        return $this->created(new PurchaseResource($purchase));
    }

    /**
     * Actualizar Compra
     */
    public function update(PurchaseRequest $request, Purchase $purchase): JsonResponse
    {
        if ($purchase->status !== 'draft') {
            return $this->error('Solo se pueden editar compras en estado borrador.', 422);
        }

        $validated = $request->validated();

        DB::transaction(function () use ($validated, $purchase) {
            $purchase->update([
                'partner_id' => $validated['partner_id'],
                'warehouse_id' => $validated['warehouse_id'],
                'vendor_bill_number' => $validated['vendor_bill_number'] ?? null,
                'vendor_bill_date' => $validated['vendor_bill_date'] ?? null,
                'observation' => $validated['observation'] ?? null,
            ]);

            $purchase->productables()->delete();

            $total = 0;
            foreach ($validated['products'] as $productData) {
                $line = $this->prepareLineData($productData);
                $tax = isset($line['tax_id']) && class_exists(Tax::class)
                    ? Tax::find($line['tax_id'])
                    : null;

                $quantity = $line['quantity'];
                $price = $line['price'];
                $subtotal = $quantity * $price;

                $taxRate = $tax ? $tax->rate_percent : 0;
                $taxAmount = $subtotal * ($taxRate / 100);
                $lineTotal = $subtotal + $taxAmount;

                $purchase->productables()->create([
                    'product_product_id' => $line['product_product_id'],
                    'quantity' => $quantity,
                    'price' => $price,
                    'subtotal' => $subtotal,
                    'tax_id' => $line['tax_id'],
                    'tax_rate' => $taxRate,
                    'tax_amount' => $taxAmount,
                    'total' => $lineTotal,
                    'uom_id' => $line['uom_id'],
                    'quantity_uom' => $line['quantity_uom'],
                    'price_uom' => $line['price_uom'],
                    'uom_factor' => $line['uom_factor'],
                ]);

                $total += $lineTotal;
            }

            $purchase->update(['total' => $total]);
        });

        return $this->success(new PurchaseResource($purchase->fresh()->load(self::PURCHASE_LOAD_RELATIONS)));
    }

    /**
     * Eliminar Compra
     */
    public function destroy(Purchase $purchase): JsonResponse
    {
        if ($purchase->status !== 'draft') {
            return $this->error('Solo se pueden eliminar compras en estado borrador.', 422);
        }

        DB::transaction(function () use ($purchase) {
            $purchase->productables()->delete();
            $purchase->delete();
        });

        return $this->noContent();
    }

    /**
     * Publicar Compra
     */
    public function post(Purchase $purchase, KardexService $kardexService): JsonResponse
    {
        if ($purchase->status !== 'draft') {
            return $this->error('Solo se pueden publicar compras en estado borrador.', 422);
        }

        DB::transaction(function () use ($purchase, $kardexService) {
            $purchase->update([
                'status' => 'posted',
            ]);

            activity()
                ->performedOn($purchase)
                ->causedBy(request()->user())
                ->log('Compra Publicada');

            $purchase->load('productables');

            foreach ($purchase->productables as $productable) {
                $kardexService->registerEntry(
                    $purchase,
                    [
                        'id' => $productable->product_product_id,
                        'quantity' => $productable->quantity,
                        'price' => $productable->price,
                        'subtotal' => $productable->subtotal,
                    ],
                    (int) $purchase->warehouse_id,
                    "Compra {$purchase->serie}-{$purchase->correlative}"
                );
            }
        });

        return $this->success(new PurchaseResource($purchase->fresh()->load(self::PURCHASE_LOAD_RELATIONS)));
    }

    /**
     * Cancelar Compra
     */
    public function cancel(Purchase $purchase, KardexService $kardexService): JsonResponse
    {
        if ($purchase->status !== 'posted') {
            return $this->error('Solo se pueden cancelar compras publicadas.', 422);
        }

        DB::transaction(function () use ($purchase, $kardexService) {
            $purchase->load('productables');

            foreach ($purchase->productables as $productable) {
                $kardexService->registerExit(
                    $purchase,
                    [
                        'id' => $productable->product_product_id,
                        'quantity' => $productable->quantity,
                    ],
                    (int) $purchase->warehouse_id,
                    "Cancelación de Compra {$purchase->serie}-{$purchase->correlative}"
                );
            }

            $purchase->update(['status' => 'cancelled']);

            activity()
                ->performedOn($purchase)
                ->causedBy(request()->user())
                ->log('Compra Cancelada');
        });

        return $this->success(new PurchaseResource($purchase->fresh()->load(self::PURCHASE_LOAD_RELATIONS)));
    }

    /**
     * Pagar Compra
     */
    public function pay(Purchase $purchase): JsonResponse
    {
        if ($purchase->status !== 'posted') {
            return $this->error('Solo se pueden pagar compras publicadas.', 422);
        }

        if ($purchase->payment_status === 'paid') {
            return $this->error('La compra ya está pagada.', 422);
        }

        DB::transaction(function () use ($purchase) {
            $purchase->update(['payment_status' => 'paid']);

            activity()
                ->performedOn($purchase)
                ->causedBy(request()->user())
                ->log('Compra Pagada');
        });

        return $this->success(new PurchaseResource($purchase->fresh()->load(self::PURCHASE_LOAD_RELATIONS)));
    }

    /**
     * @param  array<string, mixed>  $productData
     * @return array<string, mixed>
     */
    private function prepareLineData(array $productData): array
    {
        $uomId = isset($productData['uom_id']) ? (int) $productData['uom_id'] : null;

        if ($uomId) {
            $uom = UnitOfMeasure::findOrFail($uomId);
            $factor = $uom->factorToBase();

            if (! isset($productData['quantity_uom']) || ! isset($productData['price_uom'])) {
                throw ValidationException::withMessages([
                    'products' => 'quantity_uom y price_uom son requeridos cuando se selecciona una unidad de medida.',
                ]);
            }

            $quantityUom = (float) $productData['quantity_uom'];
            $priceUom = (float) $productData['price_uom'];

            // Convert to base
            $quantityBase = $quantityUom * $factor;
            $priceBase = $priceUom / $factor;

            return [
                'product_product_id' => $productData['product_product_id'],
                'quantity' => $quantityBase,
                'price' => $priceBase,
                'tax_id' => $productData['tax_id'] ?? null,
                'uom_id' => $uomId,
                'quantity_uom' => $quantityUom,
                'price_uom' => $priceUom,
                'uom_factor' => $factor,
            ];
        }

        // Legacy/Default mode
        if (! isset($productData['quantity']) || ! isset($productData['price'])) {
            throw ValidationException::withMessages([
                'products' => 'quantity y price son requeridos cuando no se selecciona una unidad de medida.',
            ]);
        }

        $quantity = (float) $productData['quantity'];
        $price = (float) $productData['price'];

        return [
            'product_product_id' => $productData['product_product_id'],
            'quantity' => $quantity,
            'price' => $price,
            'tax_id' => $productData['tax_id'] ?? null,
            'uom_id' => null,
            'quantity_uom' => null,
            'price_uom' => null,
            'uom_factor' => 1,
        ];
    }
}
