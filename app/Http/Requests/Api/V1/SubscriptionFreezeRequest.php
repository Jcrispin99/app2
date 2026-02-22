<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

final class SubscriptionFreezeRequest extends FormRequest
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
            'freeze_start_date' => ['required', 'date', 'after_or_equal:today'],
            'freeze_end_date' => ['required', 'date', 'after:freeze_start_date'],
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }
}
