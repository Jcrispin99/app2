<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class AttendanceCheckInRequest extends FormRequest
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
        return [
            // Accepts precisely Partner local ID or real Document (DNI/Passport)
            'partner_id' => ['required_without:document_number', 'nullable', 'integer', 'exists:partners,id'],
            'document_number' => ['required_without:partner_id', 'nullable', 'string', 'exists:partners,document_number'],
            
            // Manual overrides from Receptionists
            'is_manual_entry' => ['boolean'],
            'validation_message' => ['nullable', 'string', 'max:255'],
        ];
    }
}
