<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

final class Productable extends Model
{
    use SoftDeletes;
    use HasFactory;

    protected $fillable = [
        'product_product_id',
        'uom_id',
        'productable_id',
        'productable_type',
        'quantity',
        'price',
        'quantity_uom',
        'price_uom',
        'uom_factor',
        'subtotal',
        'tax_id',
        'tax_rate',
        'tax_amount',
        'total',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'price' => 'decimal:2',
        'quantity_uom' => 'decimal:2',
        'price_uom' => 'decimal:2',
        'uom_factor' => 'decimal:8',
        'subtotal' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function productable(): MorphTo
    {
        return $this->morphTo();
    }

    public function productProduct(): BelongsTo
    {
        return $this->belongsTo(ProductProduct::class, 'product_product_id');
    }

    public function tax(): BelongsTo
    {
        return $this->belongsTo(Tax::class);
    }

    public function uom(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasure::class, 'uom_id');
    }
}
