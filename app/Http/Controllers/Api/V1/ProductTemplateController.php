<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ProductTemplateRequest;
use App\Http\Resources\ProductTemplateResource;
use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\ProductTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

final class ProductTemplateController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 25);
        $search = $request->input('search');

        $query = ProductTemplate::with(['productProducts.attributeValues.attribute', 'mainImage', 'category']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhereHas('productProducts', function ($variantQuery) use ($search) {
                        $variantQuery->where('sku', 'like', "%{$search}%")
                            ->orWhere('barcode', 'like', "%{$search}%");
                    })
                    ->orWhereHas('category', function ($categoryQuery) use ($search) {
                        $categoryQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $products = $query->latest()->paginate((int) $perPage)->appends($request->query());

        return $this->success(
            ProductTemplateResource::collection($products)->response()->getData(true)
        );
    }

    public function formOptions(): JsonResponse
    {
        $categories = \App\Models\Category::query()->orderBy('name')->get();
        $attributes = Attribute::withValues()->orderBy('name')->get();

        return $this->success([
            'categories' => \App\Http\Resources\CategoryResource::collection($categories),
            'attributes' => \App\Http\Resources\AttributeResource::collection($attributes),
        ], 'Form options retrieved successfully');
    }

    public function store(ProductTemplateRequest $request): JsonResponse
    {
        $data = $request->validated();

        $productTemplate = DB::transaction(function () use ($request, $data) {
            $productTemplate = ProductTemplate::create([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'price' => $data['price'],
                'category_id' => $data['category_id'],
                'uom_id' => $data['uom_id'] ?? null,
                'is_active' => $data['is_active'] ?? true,
                'is_pos_visible' => $data['is_pos_visible'] ?? true,
                'tracks_inventory' => $data['tracks_inventory'] ?? true,
                'is_service' => $data['is_service'] ?? false,
            ]);

            $this->handleImages($request, $productTemplate);
            $this->handleAttributes($data);

            $createdVariantIds = [];

            if (! empty($data['generatedVariants'])) {
                foreach ($data['generatedVariants'] as $index => $variantData) {
                    $productProduct = $productTemplate->productProducts()->create([
                        'sku' => $variantData['sku'] ?? null,
                        'barcode' => $variantData['barcode'] ?? null,
                        'price' => $variantData['price'] ?? $productTemplate->price,
                        'cost_price' => $variantData['cost_price'] ?? 0,
                        'is_principal' => $index === 0,
                    ]);

                    $createdVariantIds[] = $productProduct->id;

                    if (! empty($variantData['attributes']) && is_array($variantData['attributes'])) {
                        foreach ($variantData['attributes'] as $attributeId => $valueName) {
                            $attributeValue = AttributeValue::where('attribute_id', $attributeId)
                                ->where('value', $valueName)
                                ->first();

                            if ($attributeValue) {
                                $productProduct->attributeValues()->syncWithoutDetaching([$attributeValue->id]);
                            }
                        }
                    }
                }
            }

            if (empty($createdVariantIds)) {
                $productTemplate->productProducts()->create([
                    'sku' => $data['sku'] ?? null,
                    'barcode' => $data['barcode'] ?? null,
                    'price' => $data['price'],
                    'cost_price' => 0,
                    'is_principal' => true,
                ]);
            }

            return $productTemplate;
        });

        $productTemplate->load([
            'category',
            'mainImage',
            'images',
            'productProducts.attributeValues.attribute',
        ]);

        return $this->created(new ProductTemplateResource($productTemplate));
    }

    public function show(ProductTemplate $productTemplate): JsonResponse
    {
        $productTemplate->load([
            'category',
            'images',
            'mainImage',
            'productProducts.attributeValues.attribute',
        ]);

        return $this->success(new ProductTemplateResource($productTemplate));
    }

    public function update(ProductTemplateRequest $request, ProductTemplate $productTemplate): JsonResponse
    {
        if ($this->isPlanProductTemplate($productTemplate)) {
            return $this->error('Este producto es interno (plan de membresía) y no se puede editar directamente.', 403);
        }

        $data = $request->validated();

        DB::transaction(function () use ($request, $productTemplate, $data) {
            $productTemplate->update([
                'name' => $data['name'] ?? $productTemplate->name,
                'description' => array_key_exists('description', $data) ? $data['description'] : $productTemplate->description,
                'price' => $data['price'] ?? $productTemplate->price,
                'category_id' => $data['category_id'] ?? $productTemplate->category_id,
                'uom_id' => array_key_exists('uom_id', $data) ? $data['uom_id'] : $productTemplate->uom_id,
                'is_active' => $data['is_active'] ?? $productTemplate->is_active,
                'is_pos_visible' => $data['is_pos_visible'] ?? $productTemplate->is_pos_visible,
                'tracks_inventory' => $data['tracks_inventory'] ?? $productTemplate->tracks_inventory,
                'is_service' => $data['is_service'] ?? $productTemplate->is_service,
            ]);

            $this->handleImagesUpdate($request, $productTemplate);
            $this->handleAttributes($data);

            if (! empty($data['generatedVariants'])) {
                $this->syncVariantsBySignature($data['generatedVariants'], $productTemplate);
            } elseif ($request->isMethod('put')) {
                // Si es un PUT (reemplazo completo) y no hay variantes, nos aseguramos que haya exactamente una.
                $existingVariant = $productTemplate->productProducts()->orderBy('is_principal', 'desc')->first();

                if ($existingVariant) {
                    $existingVariant->update([
                        'sku' => array_key_exists('sku', $data) ? $data['sku'] : $existingVariant->sku,
                        'barcode' => array_key_exists('barcode', $data) ? $data['barcode'] : $existingVariant->barcode,
                        'price' => $data['price'] ?? $existingVariant->price,
                        'is_principal' => true,
                    ]);

                    // Kill other variants if any
                    $productTemplate->productProducts()->where('id', '!=', $existingVariant->id)->delete();
                } else {
                    $productTemplate->productProducts()->create([
                        'sku' => $data['sku'] ?? null,
                        'barcode' => $data['barcode'] ?? null,
                        'price' => $data['price'] ?? $productTemplate->price,
                        'cost_price' => 0,
                        'is_principal' => true,
                    ]);
                }
            }
        });

        $productTemplate->load([
            'category',
            'mainImage',
            'images',
            'productProducts.attributeValues.attribute',
        ]);

        return $this->success(new ProductTemplateResource($productTemplate));
    }

    public function destroy(ProductTemplate $productTemplate): JsonResponse
    {
        if ($this->isPlanProductTemplate($productTemplate)) {
            return $this->error('Este producto es interno (plan de membresía) y no se puede eliminar.', 403);
        }

        if ($productTemplate->productProducts()->exists()) {
            $productTemplate->productProducts()->delete();
        }

        $productTemplate->images()->each(function ($image) {
            Storage::disk('public')->delete($image->path);
            $image->delete();
        });

        $productTemplate->delete();

        return $this->noContent();
    }

    public function toggleStatus(ProductTemplate $productTemplate): JsonResponse
    {
        if ($this->isPlanProductTemplate($productTemplate)) {
            return $this->error('Este producto es interno (plan de membresía) y no se puede modificar su estado visualmente.', 403);
        }

        $productTemplate->update([
            'is_active' => ! $productTemplate->is_active,
        ]);

        return $this->success(new ProductTemplateResource($productTemplate->fresh()->load('category')));
    }

    private function isPlanProductTemplate(ProductTemplate $productTemplate): bool
    {
        return $productTemplate->category && $productTemplate->category->name === 'Suscripciones';
    }

    /*
     |--------------------------------------------------------------------------
     | Helper Methods for Reusability and Clean Code
     |--------------------------------------------------------------------------
     */

    private function handleImages(Request $request, ProductTemplate $productTemplate): void
    {
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('images/products', 'public');
            $productTemplate->images()->create([
                'path' => $path,
            ]);
        }

        if ($request->hasFile('additionalImages')) {
            foreach ($request->file('additionalImages') as $imageFile) {
                $path = $imageFile->store('images/products', 'public');
                $productTemplate->images()->create([
                    'path' => $path,
                    'size' => $imageFile->getSize(),
                ]);
            }
        }
    }

    private function handleImagesUpdate(Request $request, ProductTemplate $productTemplate): void
    {
        $mainImageId = null;

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('images/products', 'public');
            $mainImage = $productTemplate->images()->oldest()->first();

            if ($mainImage) {
                Storage::disk('public')->delete($mainImage->path);
                $mainImage->update(['path' => $path]);
                $mainImageId = $mainImage->id;
            } else {
                $newImage = $productTemplate->images()->create(['path' => $path]);
                $mainImageId = $newImage->id;
            }
        } else {
            $mainImage = $productTemplate->images()->oldest()->first();
            if ($mainImage) {
                $mainImageId = $mainImage->id;
            }
        }

        // Only delete images if explicit instruction has been passed
        if ($request->has('existingImageIds') && is_array($request->input('existingImageIds'))) {
            $existingIds = $request->input('existingImageIds');
            $productTemplate->images()
                ->whereNotIn('id', $existingIds)
                ->when($mainImageId, function ($query) use ($mainImageId) {
                    return $query->where('id', '!=', $mainImageId);
                })
                ->each(function ($image) {
                    Storage::disk('public')->delete($image->path);
                    $image->delete();
                });
        }

        if ($request->hasFile('additionalImages')) {
            foreach ($request->file('additionalImages') as $imageFile) {
                $path = $imageFile->store('images/products', 'public');
                $productTemplate->images()->create([
                    'path' => $path,
                    'size' => $imageFile->getSize(),
                ]);
            }
        }
    }

    private function handleAttributes(array $data): void
    {
        if (empty($data['attributeLines']) || ! is_array($data['attributeLines'])) {
            return;
        }

        foreach ($data['attributeLines'] as $line) {
            if (empty($line['attribute_id']) || empty($line['values']) || ! is_array($line['values'])) {
                continue;
            }

            $attribute = Attribute::find($line['attribute_id']);
            if (! $attribute) {
                continue;
            }

            foreach ($line['values'] as $valueName) {
                $valueName = mb_trim((string) $valueName);
                if ($valueName === '') {
                    continue;
                }

                AttributeValue::firstOrCreate([
                    'attribute_id' => $attribute->id,
                    'value' => $valueName,
                ]);
            }
        }
    }

    private function syncVariantsBySignature(array $generatedVariants, ProductTemplate $productTemplate): void
    {
        $existingVariants = $productTemplate->productProducts()->with('attributeValues')->get();
        $processedIds = [];
        $signatureMap = [];

        // Build the signature map of current DB state
        foreach ($existingVariants as $variant) {
            $attributes = [];
            foreach ($variant->attributeValues as $av) {
                $attributes[$av->attribute_id] = $av->value;
            }
            ksort($attributes);
            $signatureMap[json_encode($attributes)] = $variant;
        }

        // Process incoming variants
        foreach ($generatedVariants as $variantData) {
            $attributes = [];
            if (! empty($variantData['attributes']) && is_array($variantData['attributes'])) {
                $attributes = $variantData['attributes'];
                ksort($attributes);
            }

            $signature = json_encode($attributes);
            $existing = $signatureMap[$signature] ?? null;

            if ($existing) {
                // Update
                $existing->update([
                    'sku' => array_key_exists('sku', $variantData) ? $variantData['sku'] : $existing->sku,
                    'barcode' => array_key_exists('barcode', $variantData) ? $variantData['barcode'] : $existing->barcode,
                    'price' => $variantData['price'] ?? $existing->price,
                    'cost_price' => $variantData['cost_price'] ?? $existing->cost_price,
                ]);
                $processedIds[] = $existing->id;
            } else {
                // Create
                $productProduct = $productTemplate->productProducts()->create([
                    'sku' => $variantData['sku'] ?? null,
                    'barcode' => $variantData['barcode'] ?? null,
                    'price' => $variantData['price'] ?? $productTemplate->price,
                    'cost_price' => $variantData['cost_price'] ?? 0,
                    'is_principal' => false,
                ]);

                if (! empty($variantData['attributes']) && is_array($variantData['attributes'])) {
                    foreach ($variantData['attributes'] as $attributeId => $valueName) {
                        $attributeValue = AttributeValue::where('attribute_id', $attributeId)
                            ->where('value', $valueName)
                            ->first();

                        if ($attributeValue) {
                            $productProduct->attributeValues()->syncWithoutDetaching([$attributeValue->id]);
                        }
                    }
                }

                $processedIds[] = $productProduct->id;
            }
        }

        // Delete any variant that was not processed (meaning it was removed by the user)
        $productTemplate->productProducts()->whereNotIn('id', $processedIds)->delete();

        // Ensure at least one principal variant exists if we have ANY variants left
        $remaining = $productTemplate->productProducts()->get();
        if ($remaining->isNotEmpty() && $remaining->where('is_principal', true)->isEmpty()) {
            $remaining->first()->update(['is_principal' => true]);
        }
    }
}
