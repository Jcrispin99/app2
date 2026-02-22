<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class JournalRequest extends FormRequest
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
        $journalId = $this->route('journal') ? $this->route('journal')->id : null;

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required',
                'string',
                'max:10',
                Rule::unique('journals', 'code')->ignore($journalId),
            ],
            'type' => ['required', 'string', 'in:sale,purchase,purchase-order,quote,cash'],
            'is_fiscal' => ['boolean'],
            'document_type_code' => ['nullable', 'string', 'max:2'],
            'company_id' => ['required', 'integer', Rule::exists('companies', 'id')],

            // Sequence data
            'sequence_size' => ['nullable', 'integer', 'min:4', 'max:12'],
            'step' => ['nullable', 'integer', 'min:1'],
            'next_number' => ['nullable', 'integer', 'min:1'],
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
