<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

final class UnitOfMeasure extends Model
{
    use SoftDeletes;
    use LogsActivity;

    protected $table = 'unit_of_measures';

    protected $fillable = [
        'name',
        'symbol',
        'family',
        'base_unit_id',
        'factor',
        'is_active',
    ];

    protected $casts = [
        'factor' => 'decimal:8',
        'is_active' => 'boolean',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'name',
                'symbol',
                'family',
                'base_unit_id',
                'factor',
                'is_active',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function getDescriptionForEvent(string $eventName): string
    {
        return match ($eventName) {
            'created' => 'Unidad de medida creada',
            'updated' => 'Unidad de medida actualizada',
            'deleted' => 'Unidad de medida eliminada',
            default => $eventName,
        };
    }

    public function baseUnit(): BelongsTo
    {
        return $this->belongsTo(self::class, 'base_unit_id');
    }

    public function derivedUnits(): HasMany
    {
        return $this->hasMany(self::class, 'base_unit_id');
    }

    public function productables(): HasMany
    {
        return $this->hasMany(Productable::class, 'uom_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function isBase(): bool
    {
        return $this->base_unit_id === null;
    }

    public function factorToBase(): float
    {
        if ($this->isBase()) {
            return 1.0;
        }

        $factor = (float) $this->factor;
        $current = $this->baseUnit;
        $guard = 0;

        while ($current && $current->base_unit_id !== null && $guard < 25) {
            $factor *= (float) $current->factor;
            $current = $current->baseUnit;
            $guard++;
        }

        return $factor;
    }

    public function toBaseQuantity(float $quantity): float
    {
        return $quantity * $this->factorToBase();
    }

    public function fromBaseQuantity(float $baseQuantity): float
    {
        $factor = $this->factorToBase();
        if ($factor === 0.0) {
            return 0.0;
        }

        return $baseQuantity / $factor;
    }
}
