<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Models\UnitOfMeasure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class UnitOfMeasureRequest extends FormRequest
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
        $uomId = $this->route('unit_of_measure') ? $this->route('unit_of_measure')->id : null;
        $family = $this->input('family');

        $rules = [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('unit_of_measures', 'name')
                    ->where(function ($query) use ($family) {
                        return $query->where('family', $family);
                    })
                    ->ignore($uomId),
            ],
            'symbol' => ['nullable', 'string', 'max:50'],
            'family' => ['required', 'string', 'max:255'],
            'base_unit_id' => ['nullable', 'integer', Rule::exists('unit_of_measures', 'id')],
            'factor' => ['nullable', 'numeric', 'min:0.00000001'],
            'is_active' => ['boolean'],
        ];

        // Conditional application for PATCH HTTP method updates
        if ($this->isMethod('patch') || $this->isMethod('put')) {
            $rules = array_map(function ($rule) {
                return array_merge(['sometimes'], is_array($rule) ? $rule : explode('|', $rule));
            }, $rules);
        }

        return $rules;
    }

    /**
     * Configure the validator instance with custom rules after initial passing.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $baseUnitId = $this->input('base_unit_id');
            $uomModel = $this->route('unit_of_measure'); // Will be present exclusively on updates
            $family = $this->input('family');

            if (empty($baseUnitId)) {
                return;
            }

            // Self-referencing constraint
            if ($uomModel && (int) $baseUnitId === (int) $uomModel->id) {
                $validator->errors()->add('base_unit_id', 'Una unidad de medida no puede ser su propia base.');

                return;
            }

            // Fetch Base object to evaluate constraints
            $base = UnitOfMeasure::find($baseUnitId);

            if (! $base) {
                return; // Normal 'exists' rule handles missing records
            }

            // Family integrity check
            if ($family && (string) $base->family !== (string) $family) {
                $validator->errors()->add('base_unit_id', 'La unidad base debe pertenecer a la misma familia.');
            }

            // Cycle/Loop anti-pattern evaluation exclusively on updates
            if ($uomModel && $this->wouldCreateCycle($uomModel, (int) $baseUnitId)) {
                $validator->errors()->add('base_unit_id', 'Esta configuración crea un ciclo de unidades infinito (stack overflow).');
            }
        });
    }

    /**
     * Internal cycle prevention logic
     */
    private function wouldCreateCycle(UnitOfMeasure $unit, ?int $newBaseId): bool
    {
        if ($newBaseId === null) {
            return false;
        }

        $currentId = $newBaseId;
        $guard = 0; // Stack breaker

        while ($currentId !== null && $guard < 50) {
            if ($currentId === (int) $unit->id) {
                return true;
            }

            $currentId = UnitOfMeasure::query()->whereKey($currentId)->value('base_unit_id');
            $guard++;
        }

        return false;
    }
}
