<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class PosConfigRequest extends FormRequest
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
        $rules = [
            'name' => 'required|string|max:255',
            'warehouse_id' => 'required|integer|exists:warehouses,id',
            'default_customer_id' => 'nullable|integer|exists:partners,id',
            'tax_id' => 'nullable|integer|exists:taxes,id',
            'apply_tax' => 'boolean',
            'prices_include_tax' => 'boolean',
            'is_active' => 'boolean',
            
            // Nested Journals Sync
            'journals' => 'required|array',
            'journals.*.journal_id' => 'required|integer|exists:journals,id',
            'journals.*.document_type' => 'required|string|in:invoice,receipt,credit_note,debit_note',
            'journals.*.is_default' => 'boolean',
        ];

        if ($this->isMethod('patch') || $this->isMethod('put')) {
            foreach ($rules as $field => $rule) {
                $rules[$field] = (str_starts_with($field, 'journals.')) ? $rule : 'sometimes|' . $rule;
            }
            $rules['journals'] = 'sometimes|array';
        }

        return $rules;
    }
}
