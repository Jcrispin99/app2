<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class CustomerRequest extends FormRequest
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
        $customerId = $this->route('customer') ? $this->route('customer')->id : null;

        $rules = [
            'company_id' => ['nullable', 'exists:companies,id'],
            'document_type' => ['required', 'in:DNI,RUC,CE,Passport'],
            'document_number' => [
                'required',
                'string',
                'max:20',
                Rule::unique('partners', 'document_number')
                    ->ignore($customerId)
                    ->where(fn ($q) => $q->where('document_type', $this->input('document_type'))),
            ],
            'name' => ['required', 'string', 'max:200'],
            'email' => ['nullable', 'email', 'max:100'],
            'phone' => ['nullable', 'string', 'max:20'],
            'mobile' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string'],
            'ubigeo' => ['nullable', 'string', 'max:6'],
            'birth_date' => ['nullable', 'date'],
            'gender' => ['nullable', 'in:M,F,Other'],
            'photo_url' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'status' => ['nullable', 'in:active,inactive,suspended,blacklisted'],
        ];

        if ($this->isMethod('patch')) {
            $rules = array_map(function ($rule) {
                if (is_array($rule)) {
                    array_unshift($rule, 'sometimes');

                    return $rule;
                }

                return 'sometimes|'.$rule;
            }, $rules);
        }

        return $rules;
    }
}
