<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Tax extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'name',
        'description',
        'invoice_label',
        'tax_type',
        'affectation_type_code',
        'rate_percent',
        'is_price_inclusive',
        'is_active',
        'is_default',
    ];

    protected $casts = [
        'rate_percent' => 'decimal:2',
        'is_price_inclusive' => 'boolean',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
    ];

    public function productables(): HasMany
    {
        return $this->hasMany(Productable::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('tax_type', $type);
    }
}
