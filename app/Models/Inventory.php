<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

final class Inventory extends Model
{
    protected $fillable = [
        'detail',
        'quantity_in',
        'cost_in',
        'total_in',
        'quantity_out',
        'cost_out',
        'total_out',
        'quantity_balance',
        'cost_balance',
        'total_balance',
        'product_product_id',
        'warehouse_id',
        'inventoryable_type',
        'inventoryable_id',
    ];

    protected $casts = [
        'quantity_in' => 'integer',
        'cost_in' => 'decimal:2',
        'total_in' => 'decimal:2',
        'quantity_out' => 'integer',
        'cost_out' => 'decimal:2',
        'total_out' => 'decimal:2',
        'quantity_balance' => 'integer',
        'cost_balance' => 'decimal:2',
        'total_balance' => 'decimal:2',
    ];

    public function productProduct(): BelongsTo
    {
        return $this->belongsTo(ProductProduct::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function inventoryable(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeForProduct($query, $productProductId)
    {
        return $query->where('product_product_id', $productProductId);
    }

    public function scopeForWarehouse($query, $warehouseId)
    {
        return $query->where('warehouse_id', $warehouseId);
    }

    public function scopeLatestBalance($query)
    {
        return $query->orderBy('created_at', 'desc')->first();
    }
}
