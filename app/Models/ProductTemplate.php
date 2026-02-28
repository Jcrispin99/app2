<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

final class ProductTemplate extends Model
{
    use SoftDeletes;
    use HasFactory, LogsActivity;

    protected $table = 'product_templates';

    protected $fillable = [
        'name',
        'description',
        'price',
        'category_id',
        'uom_id',
        'is_active',
        'is_pos_visible',
        'tracks_inventory',
        'is_service',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
        'is_pos_visible' => 'boolean',
        'tracks_inventory' => 'boolean',
        'is_service' => 'boolean',
    ];

    protected $appends = [];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function uom(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasure::class, 'uom_id');
    }

    public function productProducts(): HasMany
    {
        return $this->hasMany(ProductProduct::class, 'product_template_id')->orderBy('is_principal', 'desc');
    }

    public function variants(): HasMany
    {
        return $this->productProducts();
    }

    public function images(): MorphMany
    {
        return $this->morphMany(Imageable::class, 'imageable');
    }

    public function mainImage(): MorphOne
    {
        return $this->morphOne(Imageable::class, 'imageable')->oldestOfMany();
    }

    public function image(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->mainImage ? Storage::url($this->mainImage->path) : null,
        );
    }

    public function sku(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->variants->first()?->sku,
        );
    }

    public function barcode(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->variants->first()?->barcode,
        );
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeWithCategory($query)
    {
        return $query->with('category');
    }

    public function scopeWithVariants($query)
    {
        return $query->with('variants');
    }

    public function scopeWithImages($query)
    {
        return $query->with('images');
    }

    public function getPrincipalVariant()
    {
        return $this->variants()->where('is_principal', true)->first();
    }

    public function getTotalStock(): int
    {
        return $this->productProducts()->sum('stock');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'name',
                'description',
                'price',
                'category_id',
                'is_active',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
