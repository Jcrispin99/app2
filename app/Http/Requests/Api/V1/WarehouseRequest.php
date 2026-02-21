<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

final class WarehouseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authentication handled by middleware
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'company_id' => ['nullable', 'exists:companies,id'],
        ];

        // Conditional validation for PATCH requests
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
