<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

final class MembershipSubscriptionRequest extends FormRequest
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
            'partner_id' => ['required', 'integer', 'exists:partners,id'],
            'membership_plan_id' => ['required', 'integer', 'exists:membership_plans,id'],
            'start_date' => ['nullable', 'date', 'after_or_equal:today'],
            'amount_paid' => ['required', 'numeric', 'min:0'],
            'payment_method' => ['required', 'string', 'max:50'],
            'payment_reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:500'],
            'company_id' => ['required', 'integer', 'exists:companies,id'], // Fallback Context
        ];
    }

    /**
     * Handle payload preprocessing before validation kicks in.
     */
    protected function prepareForValidation(): void
    {
        if (! $this->has('company_id') && auth()->check() && auth()->user()->company_id) {
            $this->merge([
                'company_id' => auth()->user()->company_id,
            ]);
        }

        if (! $this->has('start_date')) {
            $this->merge([
                'start_date' => now()->toDateString(),
            ]);
        }
    }
}
