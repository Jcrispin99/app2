<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class TaxRequest extends FormRequest
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
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $taxId = $this->route('tax') ? $this->route('tax')->id : null;

        $rules = [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('taxes', 'name')->ignore($taxId),
            ],
            'description' => ['nullable', 'string'],
            'invoice_label' => ['nullable', 'string', 'max:255'],
            'tax_type' => ['required', 'string'],
            'affectation_type_code' => ['nullable', 'string', 'max:10'],
            'rate_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'is_price_inclusive' => ['boolean'],
            'is_active' => ['boolean'],
            'is_default' => ['boolean'],
        ];

        // Conditional application for PATCH HTTP method updates
        if ($this->isMethod('patch') || $this->isMethod('put')) {
            $rules = array_map(function ($rule) {
                return array_merge(['sometimes'], is_array($rule) ? $rule : explode('|', $rule));
            }, $rules);
        }

        return $rules;
    }
}
