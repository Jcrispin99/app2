<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MemberRequest extends FormRequest
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
        $memberId = $this->route('member')?->id;

        $rules = [
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],

            // Documentos
            'document_type' => ['required', 'string', 'in:DNI,RUC,CE,Passport'],
            'document_number' => [
                'required',
                'string',
                'max:20',
            ],

            // Datos Base
            'name' => ['required', 'string', 'max:200'],
            'email' => ['nullable', 'email', 'max:100'],
            'phone' => ['nullable', 'string', 'max:20'],
            'mobile' => ['nullable', 'string', 'max:20'],

            // Dirección
            'address' => ['nullable', 'string'],
            'ubigeo' => ['nullable', 'string', 'max:6'],

            // Demográficos (disponibles en la BD)
            'birth_date' => ['nullable', 'date'],
            'gender' => ['nullable', 'string', 'in:M,F,Other'],

            // Notas
            'notes' => ['nullable', 'string'],
        ];

        if ($this->isMethod('patch') || $this->isMethod('put')) {
            // Document uniqueness rule for update
            $rules['document_number'] = [
                'required',
                'string',
                'max:20',
                Rule::unique('partners', 'document_number')
                    ->ignore($memberId)
                    ->where(fn($q) => $q->where('document_type', $this->input('document_type'))),
            ];

            // Status is required only on update
            $rules['status'] = ['required', 'string', 'in:active,inactive,suspended,blacklisted'];

            // Make rules optional for precise PATCH updates
            foreach ($rules as $key => $ruleSet) {
                if (is_array($ruleSet)) {
                    array_unshift($rules[$key], 'sometimes');
                } else {
                    $rules[$key] = 'sometimes|' . $ruleSet;
                }
            }
        }

        return $rules;
    }
}
