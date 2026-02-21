<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class MembershipPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'product_product_id',
        'name',
        'description',
        'duration_days',
        'price',
        'max_entries_per_month',
        'max_entries_per_day',
        'time_restricted',
        'allowed_time_start',
        'allowed_time_end',
        'allowed_days',
        'allows_freezing',
        'max_freeze_days',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'time_restricted' => 'boolean',
        'allowed_time_start' => 'datetime:H:i',
        'allowed_time_end' => 'datetime:H:i',
        'allowed_days' => 'array',
        'allows_freezing' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function productProduct(): BelongsTo
    {
        return $this->belongsTo(ProductProduct::class, 'product_product_id');
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(MembershipSubscription::class);
    }

    public function activeSubscriptions(): HasMany
    {
        return $this->hasMany(MembershipSubscription::class)
            ->where('status', 'active')
            ->where('end_date', '>=', now()->toDateString());
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function isUnlimitedEntries(): bool
    {
        return is_null($this->max_entries_per_month);
    }

    public function hasTimeRestriction(): bool
    {
        return $this->time_restricted;
    }

    public function hasDayRestriction(): bool
    {
        return ! is_null($this->allowed_days) && count($this->allowed_days) > 0;
    }

    public function allowsFreeze(): bool
    {
        return $this->allows_freezing && $this->max_freeze_days > 0;
    }

    public function getDurationInMonths(): int
    {
        return (int) ($this->duration_days / 30);
    }

    public function getFormattedPrice(): string
    {
        return 'S/ '.number_format($this->price, 2);
    }

    protected static function booted()
    {
        self::updated(function (self $plan) {
            if (! $plan->wasChanged(['name', 'description', 'price'])) {
                return;
            }

            if (empty($plan->product_product_id)) {
                return;
            }

            $variant = ProductProduct::with('product')->find($plan->product_product_id);
            if (! $variant || ! $variant->product) {
                return;
            }

            $category = Category::firstOrCreate(
                ['name' => 'Suscripciones', 'parent_id' => null],
                [
                    'name' => 'Suscripciones',
                    'full_name' => 'Suscripciones',
                    'description' => 'Productos internos para planes y suscripciones.',
                    'is_active' => false,
                ]
            );

            $variant->product->update([
                'name' => "Plan: {$plan->name}",
                'description' => $plan->description,
                'price' => $plan->price,
                'category_id' => $category->id,
                'is_active' => true,
                'is_pos_visible' => false,
                'tracks_inventory' => false,
                'is_service' => true,
            ]);

            $variant->update([
                'price' => $plan->price,
            ]);
        });
    }
}
