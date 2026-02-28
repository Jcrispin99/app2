<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

final class ProductProduct extends Model
{
    use SoftDeletes;
    use LogsActivity;

    protected $table = 'product_products';

    protected $fillable = [
        'product_template_id',
        'sku',
        'barcode',
        'price',
        'cost_price',
        'is_principal',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'is_principal' => 'boolean',
    ];

    protected $appends = ['stock'];

    public static function generateUniqueBarcode(): string
    {
        do {
            $barcode = self::generateEAN13();
        } while (self::where('barcode', $barcode)->exists());

        return $barcode;
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(ProductTemplate::class, 'product_template_id');
    }

    public function productTemplate(): BelongsTo
    {
        return $this->product();
    }

    public function template(): BelongsTo
    {
        return $this->product();
    }

    public function attributeValues(): BelongsToMany
    {
        return $this->belongsToMany(AttributeValue::class, 'attribute_value_products', 'product_product_id');
    }

    public function inventories()
    {
        return $this->hasMany(Inventory::class, 'product_product_id');
    }

    public function productables(): HasMany
    {
        return $this->hasMany(Productable::class, 'product_product_id');
    }

    public function getStockAttribute(): int
    {
        $latestInventory = $this->inventories()
            ->orderBy('created_at', 'desc')
            ->first();

        return $latestInventory ? $latestInventory->quantity_balance : 0;
    }

    public function getStockInWarehouse($warehouseId): int
    {
        $latestInventory = $this->inventories()
            ->where('warehouse_id', $warehouseId)
            ->orderBy('created_at', 'desc')
            ->first();

        return $latestInventory ? $latestInventory->quantity_balance : 0;
    }

    public function getDisplayNameAttribute(): string
    {
        $productName = $this->product->name ?? 'Producto';
        $attributes = $this->attributeValues->pluck('value')->join(' - ');

        return $attributes ? "{$productName} - {$attributes}" : $productName;
    }

    public function getAttributeStringAttribute(): string
    {
        return $this->attributeValues->pluck('value')->join(', ') ?: 'Sin atributos';
    }

    public function scopePrincipal($query)
    {
        return $query->where('is_principal', true);
    }

    public function scopeInStock($query)
    {
        return $query->where('stock', '>', 0);
    }

    public function scopeWithProduct($query)
    {
        return $query->with('product');
    }

    public function scopeWithAttributes($query)
    {
        return $query->with('attributeValues.attribute');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'product_template_id',
                'sku',
                'barcode',
                'price',
                'is_principal',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected static function boot()
    {
        parent::boot();

        self::creating(function ($productProduct) {
            if (empty($productProduct->barcode)) {
                $productProduct->barcode = static::generateUniqueBarcode();
            }
        });
    }

    private static function generateEAN13(): string
    {
        $prefix = '77';

        $randomDigits = '';
        for ($i = 0; $i < 10; $i++) {
            $randomDigits .= rand(0, 9);
        }

        $barcode = $prefix.$randomDigits;

        $checksum = self::calculateEAN13Checksum($barcode);

        return $barcode.$checksum;
    }

    private static function calculateEAN13Checksum(string $barcode): int
    {
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $digit = (int) $barcode[$i];
            $sum += ($i % 2 === 0) ? $digit : $digit * 3;
        }

        $checksum = (10 - ($sum % 10)) % 10;

        return $checksum;
    }
}
