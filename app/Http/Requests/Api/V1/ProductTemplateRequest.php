<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

final class ProductTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'category_id' => ['required', 'exists:categories,id'],
            'uom_id' => ['nullable', 'exists:unit_of_measures,id'],
            'is_active' => ['nullable', 'boolean'],
            'is_pos_visible' => ['nullable', 'boolean'],
            'tracks_inventory' => ['nullable', 'boolean'],
            'is_service' => ['nullable', 'boolean'],
            'sku' => ['nullable', 'string', 'max:255'],
            'barcode' => ['nullable', 'string', 'max:255'],

            // Image handling
            'image' => ['nullable', 'image', 'max:10240'],
            'existingImageIds' => ['nullable', 'array'],
            'existingImageIds.*' => ['integer', 'exists:images,id'],
            'additionalImages' => ['nullable', 'array'],
            'additionalImages.*' => ['nullable', 'image', 'max:10240'],

            // Variants generation parameters
            'attributeLines' => ['nullable', 'array'],
            'attributeLines.*.attribute_id' => ['required_with:attributeLines', 'integer', 'exists:attributes,id'],
            'attributeLines.*.values' => ['required_with:attributeLines', 'array'],
            'attributeLines.*.values.*' => ['string', 'max:255'],

            // Exact combinations to create/update
            'generatedVariants' => ['nullable', 'array'],
            'generatedVariants.*.sku' => ['nullable', 'string', 'max:255'],
            'generatedVariants.*.barcode' => ['nullable', 'string', 'max:255'],
            'generatedVariants.*.price' => ['nullable', 'numeric', 'min:0'],
            'generatedVariants.*.cost_price' => ['nullable', 'numeric', 'min:0'],
            'generatedVariants.*.attributes' => ['nullable', 'array'],
            // the attributes array in variant is structured as [attribute_id => "Value Name"]
        ];

        if ($this->isMethod('patch')) {
            $rules = array_map(function ($rule) {
                // If the rule is already an array, prepend sometimes, otherwise explode it
                if (is_array($rule)) {
                    array_unshift($rule, 'sometimes');

                    return $rule;
                }

                return 'sometimes|'.$rule;
            }, $rules);
        }

        return $rules;
    }

    public function attributes(): array
    {
        return [
            'category_id' => 'categoría',
            'uom_id' => 'unidad de medida',
        ];
    }
}
