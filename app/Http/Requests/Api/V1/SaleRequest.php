<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

final class SaleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $sale = $this->route('sale');

        // Allow partial updates only on notes for posted/cancelled sales
        if ($sale && $sale->status !== 'draft') {
            return [
                'notes' => ['nullable', 'string'],
            ];
        }

        $rules = [
            'partner_id' => ['nullable', 'integer', 'exists:partners,id'],
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'notes' => ['nullable', 'string'],

            'products' => ['required', 'array', 'min:1'],
            'products.*.product_product_id' => ['required', 'integer', 'exists:product_products,id'],
            'products.*.tax_id' => ['nullable', 'integer', 'exists:taxes,id'],

            // Generic Unit Of Measure properties
            'products.*.uom_id' => ['nullable', 'integer', 'exists:unit_of_measures,id'],
            'products.*.quantity_uom' => ['nullable', 'numeric', 'min:0.01'],
            'products.*.price_uom' => ['nullable', 'numeric', 'min:0'],
        ];

        // Specific legacy lines handling
        if ($this->has('products') && is_array($this->input('products'))) {
            foreach ($this->input('products') as $index => $productData) {
                if (isset($productData['uom_id'])) {
                    $rules["products.{$index}.quantity_uom"] = ['required', 'numeric', 'min:0.01'];
                    $rules["products.{$index}.price_uom"] = ['required', 'numeric', 'min:0'];
                } else {
                    $rules["products.{$index}.quantity"] = ['required', 'numeric', 'min:0.01'];
                    $rules["products.{$index}.price"] = ['required', 'numeric', 'min:0'];
                }
            }
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'products.required' => 'La venta debe contener al menos un producto.',
            'products.*.product_product_id.required' => 'El identificador del producto es obligatorio en cada línea.',
            'products.*.quantity.required' => 'La cantidad base es obligatoria cuando no se provee unidad de medida.',
            'products.*.price.required' => 'El precio base es obligatorio cuando no se provee unidad de medida.',
            'products.*.quantity_uom.required' => 'La cantidad en la unidad seleccionada es obligatoria.',
            'products.*.price_uom.required' => 'El precio en la unidad seleccionada es obligatorio.',
        ];
    }
}
