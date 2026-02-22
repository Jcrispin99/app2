<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class PosSessionRequest extends FormRequest
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
        // Route handles either POST (open) or PATCH (close)
        if ($this->isMethod('patch') || $this->isMethod('put')) {
            return [
                'closing_balance' => 'required|numeric|min:0',
                'closing_note' => 'nullable|string',
            ];
        }

        return [
            'pos_config_id' => 'required|integer|exists:pos_configs,id',
            'opening_balance' => 'required|numeric|min:0',
            'opening_note' => 'nullable|string',
        ];
    }
}
