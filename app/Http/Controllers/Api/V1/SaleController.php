<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\SaleRequest;
use App\Http\Resources\SaleResource;
use App\Models\Journal;
use App\Models\Partner;
use App\Models\PosSession;
use App\Models\PosSessionPayment;
use App\Models\Sale;
use App\Models\Tax;
use App\Models\UnitOfMeasure;
use App\Models\Warehouse;
use App\Services\KardexService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Activity;

final class SaleController extends Controller
{
    /**
     * @var array<string, array<string, mixed>>
     */
    private const SALE_LOAD_RELATIONS = [
        'partner',
        'warehouse',
        'company',
        'journal',
        'user',
        'products.productProduct.template.uom',
        'products.productProduct.attributeValues',
        'products.tax',
        'products.uom',
        'originalSale.journal',
        'creditNotes.journal',
    ];

    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 25);
        $search = $request->input('search');
        $status = $request->input('status');
        $paymentStatus = $request->input('payment_status');
        $from = $request->input('from');
        $to = $request->input('to');

        $query = Sale::query()
            ->with(['partner', 'warehouse', 'company', 'journal', 'user'])
            ->orderBy('created_at', 'desc');

        if ($status) {
            $query->where('status', $status);
        }

        if ($paymentStatus) {
            $query->where('payment_status', $paymentStatus);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('serie', 'like', "%{$search}%")
                    ->orWhere('correlative', 'like', "%{$search}%")
                    ->orWhereHas('partner', function ($partnerQuery) use ($search) {
                        $partnerQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('document_number', 'like', "%{$search}%");
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
        $cancelledTotal = (clone $query)->where('status', 'cancelled')->count();

        $paginator = $query->paginate((int) $perPage)->appends($request->query());

        return response()->json([
            'success' => true,
            'message' => 'Sales listing retrieved successfully',
            'data' => SaleResource::collection($paginator->items()),
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
                'cancelled_total' => $cancelledTotal,
            ],
        ]);
    }

    public function formOptions(): JsonResponse
    {
        $customers = Partner::query()
            ->customers()
            ->orderBy('name')
            ->get();

        $warehouses = Warehouse::query()->latest()->get();

        $taxes = [];
        if (class_exists(Tax::class)) {
            $taxes = Tax::query()
                ->where('is_active', true)
                ->orderByDesc('is_default')
                ->orderBy('name')
                ->get();
        }

        return $this->success([
            'customers' => \App\Http\Resources\CustomerResource::collection($customers),
            'warehouses' => \App\Http\Resources\WarehouseResource::collection($warehouses),
            'taxes' => $taxes,
        ], 'Form options retrieved successfully');
    }

    public function show(Sale $sale): JsonResponse
    {
        $sale->load(self::SALE_LOAD_RELATIONS);

        $activities = Activity::forSubject($sale)
            ->with('causer')
            ->latest()
            ->take(20)
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Record retrieved successfully',
            'data' => new SaleResource($sale),
            'meta' => [
                'activities' => $activities,
            ],
        ]);
    }

    public function store(SaleRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $saleId = null;

        $companyId = $validated['company_id'] ?? $request->user()?->company_id;

        if (! $companyId) {
            return $this->error('No se pudo determinar la compañía.', 422);
        }

        DB::transaction(function () use ($validated, &$saleId, $companyId) {
            $defaultJournal = null;

            if (!empty($validated['pos_session_id'])) {
                // Find Session > Config > Default Journal
                $posSession = PosSession::with('posConfig.journals')->find($validated['pos_session_id']);
                if ($posSession && $posSession->posConfig) {
                    // Try to retrieve the 'is_default' journal from pivot, or fallback to first one
                    $defaultJournal = $posSession->posConfig->journals()
                        ->wherePivot('is_default', true)
                        ->first() ?? $posSession->posConfig->journals->first();
                }
            }

            if (!$defaultJournal) {
                // Standard Sale Journal Lookup
                $defaultJournal = Journal::where('type', 'sale')
                    ->where('company_id', $companyId)
                    ->first();
            }

            if (! $defaultJournal) {
                throw ValidationException::withMessages([
                    'journal' => 'No se encontró un diario de ventas disponible (ni global, ni asignado a la Caja).',
                ]);
            }

            $serie = 'B001';
            $correlative = '0000001';

            if ($defaultJournal->sequence) {
                $serie = $defaultJournal->code;
                $correlative = str_pad((string) $defaultJournal->sequence->next_number, $defaultJournal->sequence->sequence_size, '0', STR_PAD_LEFT);
                
                // Consumir permanentemente el número (avanzar +1)
                $defaultJournal->sequence->increment('next_number', $defaultJournal->sequence->step);
            }

            $sale = Sale::create([
                'partner_id' => $validated['partner_id'] ?? null,
                'warehouse_id' => $validated['warehouse_id'],
                'pos_session_id' => $validated['pos_session_id'] ?? null,
                'journal_id' => $defaultJournal->id,
                'company_id' => $companyId,
                'notes' => $validated['notes'] ?? null,
                'status' => 'draft',
                'payment_status' => 'unpaid',
                'user_id' => request()->user()?->id,
                'serie' => $serie,
                'correlative' => $correlative,
                'subtotal' => 0,
                'tax_amount' => 0,
                'total' => 0,
            ]);

            $subtotal = 0;
            $totalTax = 0;

            foreach ($validated['products'] as $productData) {
                $line = $this->prepareLineData($productData);
                $tax = isset($line['tax_id']) && class_exists(Tax::class)
                    ? Tax::find($line['tax_id'])
                    : null;

                $quantity = $line['quantity'];
                $price = $line['price'];
                $lineSubtotal = $quantity * $price;

                $taxRate = $tax ? $tax->rate_percent : 0;
                $taxAmount = $lineSubtotal * ($taxRate / 100);
                $lineTotal = $lineSubtotal + $taxAmount;

                $sale->products()->create([
                    'product_product_id' => $line['product_product_id'],
                    'quantity' => $quantity,
                    'price' => $price,
                    'subtotal' => $lineSubtotal,
                    'tax_id' => $line['tax_id'],
                    'tax_rate' => $taxRate,
                    'tax_amount' => $taxAmount,
                    'total' => $lineTotal,
                    'uom_id' => $line['uom_id'],
                    'quantity_uom' => $line['quantity_uom'],
                    'price_uom' => $line['price_uom'],
                    'uom_factor' => $line['uom_factor'],
                ]);

                $subtotal += $lineSubtotal;
                $totalTax += $taxAmount;
            }

            $sale->update([
                'subtotal' => $subtotal,
                'tax_amount' => $totalTax,
                'total' => $subtotal + $totalTax,
            ]);
            $saleId = $sale->id;

            // Log Partial POS Payments if requested via POS
            if (!empty($validated['pos_session_id']) && !empty($validated['payments'])) {
                foreach ($validated['payments'] as $payment) {
                    PosSessionPayment::create([
                        'pos_session_id' => $validated['pos_session_id'],
                        'sale_id' => $sale->id,
                        'payment_method_id' => $payment['payment_method_id'],
                        'amount' => $payment['amount'],
                    ]);
                }
                
                // If POS sets the payment immediately
                $sale->update(['payment_status' => 'paid']);
            }
        });

        $sale = Sale::query()
            ->with(self::SALE_LOAD_RELATIONS)
            ->findOrFail($saleId);

        return $this->created(new SaleResource($sale));
    }

    public function update(SaleRequest $request, Sale $sale): JsonResponse
    {
        $validated = $request->validated();

        if ($sale->status !== 'draft') {
            $sale->update([
                'notes' => $validated['notes'] ?? $sale->notes,
            ]);

            return $this->success(new SaleResource($sale->fresh()->load(self::SALE_LOAD_RELATIONS)));
        }

        DB::transaction(function () use ($validated, $sale) {
            $sale->update([
                'partner_id' => $validated['partner_id'] ?? null,
                'warehouse_id' => $validated['warehouse_id'],
                'notes' => $validated['notes'] ?? null,
            ]);

            $sale->products()->delete();

            $subtotal = 0;
            $totalTax = 0;

            foreach ($validated['products'] as $productData) {
                $line = $this->prepareLineData($productData);
                $tax = isset($line['tax_id']) && class_exists(Tax::class)
                    ? Tax::find($line['tax_id'])
                    : null;

                $quantity = $line['quantity'];
                $price = $line['price'];
                $lineSubtotal = $quantity * $price;

                $taxRate = $tax ? $tax->rate_percent : 0;
                $taxAmount = $lineSubtotal * ($taxRate / 100);
                $lineTotal = $lineSubtotal + $taxAmount;

                $sale->products()->create([
                    'product_product_id' => $line['product_product_id'],
                    'quantity' => $quantity,
                    'price' => $price,
                    'subtotal' => $lineSubtotal,
                    'tax_id' => $line['tax_id'],
                    'tax_rate' => $taxRate,
                    'tax_amount' => $taxAmount,
                    'total' => $lineTotal,
                    'uom_id' => $line['uom_id'],
                    'quantity_uom' => $line['quantity_uom'],
                    'price_uom' => $line['price_uom'],
                    'uom_factor' => $line['uom_factor'],
                ]);

                $subtotal += $lineSubtotal;
                $totalTax += $taxAmount;
            }

            $sale->update([
                'subtotal' => $subtotal,
                'tax_amount' => $totalTax,
                'total' => $subtotal + $totalTax,
            ]);
        });

        return $this->success(new SaleResource($sale->fresh()->load(self::SALE_LOAD_RELATIONS)));
    }

    public function destroy(Sale $sale): JsonResponse
    {
        if ($sale->status !== 'draft') {
            return $this->error('Solo se pueden eliminar ventas en estado borrador.', 422);
        }

        DB::transaction(function () use ($sale) {
            $sale->products()->delete();
            $sale->delete();
        });

        return $this->noContent();
    }

    public function post(Sale $sale, KardexService $kardexService): JsonResponse
    {
        if ($sale->status !== 'draft') {
            return $this->error('Solo se pueden publicar ventas en estado borrador.', 422);
        }

        DB::transaction(function () use ($sale, $kardexService) {
            $sale->update([
                'status' => 'posted',
            ]);

            activity()
                ->performedOn($sale)
                ->causedBy(request()->user())
                ->log('Venta Publicada y Aprobada Internamente');

            $sale->load('products.productProduct.template');

            foreach ($sale->products as $productable) {
                $tracksInventory = $productable->productProduct?->template?->tracks_inventory ?? true;
                if (! $tracksInventory) {
                    continue;
                }

                $kardexService->registerExit(
                    $sale,
                    [
                        'id' => $productable->product_product_id,
                        'quantity' => $productable->quantity,
                    ],
                    (int) $sale->warehouse_id,
                    "Venta {$sale->serie}-{$sale->correlative}"
                );
            }
        });

        return $this->success(new SaleResource($sale->fresh()->load(self::SALE_LOAD_RELATIONS)));
    }

    public function cancel(Sale $sale, KardexService $kardexService): JsonResponse
    {
        if ($sale->status !== 'posted') {
            return $this->error('Solo se pueden cancelar ventas previamente publicadas.', 422);
        }

        DB::transaction(function () use ($sale, $kardexService) {
            $sale->load('products.productProduct.template');

            foreach ($sale->products as $productable) {
                $tracksInventory = $productable->productProduct?->template?->tracks_inventory ?? true;
                if (! $tracksInventory) {
                    continue;
                }

                $lastRecord = $kardexService->getLastRecord($productable->product_product_id, (int) $sale->warehouse_id);
                $currentCost = $lastRecord['cost'] ?? 0;

                $kardexService->registerEntry(
                    $sale,
                    [
                        'id' => $productable->product_product_id,
                        'quantity' => $productable->quantity,
                        'price' => $currentCost,
                        'subtotal' => $productable->quantity * $currentCost,
                    ],
                    (int) $sale->warehouse_id,
                    "Devolución por Venta Cancelada {$sale->serie}-{$sale->correlative}"
                );
            }

            $sale->update(['status' => 'cancelled']);

            activity()
                ->performedOn($sale)
                ->causedBy(request()->user())
                ->log('Venta Cancelada (Inventario Retornado)');
        });

        return $this->success(new SaleResource($sale->fresh()->load(self::SALE_LOAD_RELATIONS)));
    }

    public function createCreditNote(Sale $sale): JsonResponse
    {
        $sale->loadMissing(['products', 'journal']);

        if ($sale->status !== 'posted') {
            return $this->error('Solo se pueden emitir notas de crédito sobre ventas publicadas.', 422);
        }

        if (empty($sale->serie) || empty($sale->correlative)) {
            return $this->error('La venta carece de numeración. No se puede crear nota.', 422);
        }

        $creditSale = null;

        // Abstracted logic: In this version we merely duplicate the sale as a draft credit note
        // so the user can edit it before posting and triggering the Return Entry for Kardex.
        DB::transaction(function () use ($sale, &$creditSale) {
            // Locate a credit note journal (Document type 07 logically, or just the first credit journal present)
            $creditJournal = Journal::where('company_id', $sale->company_id)
                ->where(function ($q) {
                    $q->where('type', 'sale_refund')
                        ->orWhere('document_type_code', '07');
                })
                ->first();

            if (! $creditJournal) {
                // Warning logic, but since it's an API, best approach is throwing standard ValidationException or abort
                throw ValidationException::withMessages([
                    'journal' => 'No se encontró un diario destino para la Nota de Crédito. Por favor configúrelo.',
                ]);
            }

            $serie = 'FC01';
            $correlative = '0000001';

            if ($creditJournal->sequence) {
                $serie = $creditJournal->code;
                $correlative = str_pad((string) $creditJournal->sequence->next_number, $creditJournal->sequence->sequence_size, '0', STR_PAD_LEFT);
                $creditJournal->sequence->increment('next_number', $creditJournal->sequence->step);
            }

            $creditSale = Sale::create([
                'partner_id' => $sale->partner_id,
                'warehouse_id' => $sale->warehouse_id,
                'journal_id' => $creditJournal->id,
                'company_id' => $sale->company_id,
                'original_sale_id' => $sale->id,
                'user_id' => request()->user()?->id,
                'notes' => 'Nota de Crédito generada a partir de '.$sale->serie.'-'.$sale->correlative,
                'status' => 'draft',
                'payment_status' => 'paid',
                'serie' => $serie,
                'correlative' => $correlative,
                'subtotal' => $sale->subtotal,
                'tax_amount' => $sale->tax_amount,
                'total' => $sale->total,
            ]);

            foreach ($sale->products as $line) {
                $creditSale->products()->create([
                    'product_product_id' => $line->product_product_id,
                    'quantity' => $line->quantity,
                    'price' => $line->price,
                    'subtotal' => $line->subtotal,
                    'tax_id' => $line->tax_id,
                    'tax_rate' => $line->tax_rate,
                    'tax_amount' => $line->tax_amount,
                    'total' => $line->total,
                    'uom_id' => $line->uom_id,
                    'quantity_uom' => $line->quantity_uom,
                    'price_uom' => $line->price_uom,
                    'uom_factor' => $line->uom_factor,
                ]);
            }
        });

        $creditSale->load(self::SALE_LOAD_RELATIONS);

        activity()
            ->performedOn($creditSale)
            ->event('created')
            ->withProperties(['origin_sale_id' => $sale->id])
            ->log('Borrador de Nota de Crédito creado desde Venta Origen');

        return $this->created(new SaleResource($creditSale));
    }

    /**
     * Parse and map Line amounts handling UoM
     *
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
