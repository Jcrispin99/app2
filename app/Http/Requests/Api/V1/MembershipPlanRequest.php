<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

final class MembershipPlanRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'duration_days' => ['required', 'integer', 'min:1'],
            'price' => ['required', 'numeric', 'min:0'],
            'max_entries_per_month' => ['nullable', 'integer', 'min:1'],
            'max_entries_per_day' => ['nullable', 'integer', 'min:1'],
            'time_restricted' => ['boolean'],
            'allowed_time_start' => ['nullable', 'date_format:H:i'],
            'allowed_time_end' => ['nullable', 'date_format:H:i', 'after:allowed_time_start'],
            'allowed_days' => ['nullable', 'array'],
            'allowed_days.*' => ['string', 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday'],
            'allows_freezing' => ['boolean'],
            'max_freeze_days' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
            'company_id' => ['required', 'integer', 'exists:companies,id'], // Can be forced by controller fallback, depending on system central rules
        ];

        // Conditional application for PATCH updates
        if ($this->isMethod('patch') || $this->isMethod('put')) {
            $rules = array_map(function ($rule) {
                return array_merge(['sometimes'], is_array($rule) ? $rule : explode('|', $rule));
            }, $rules);
        }

        return $rules;
    }

    /**
     * Handle payload preprocessing before validation kicks in.
     */
    protected function prepareForValidation(): void
    {
        // Infer Company strictly from User if not centrally provided
        // Remove this if your auth user isn't directly bound to a company!
        if (! $this->has('company_id') && auth()->check() && auth()->user()->company_id) {
            $this->merge([
                'company_id' => auth()->user()->company_id,
            ]);
        }
    }
}
