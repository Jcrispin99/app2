<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

final class Partner extends Model
{
    use SoftDeletes;
    use HasFactory, LogsActivity;

    protected $fillable = [
        'company_id',
        'user_id',
        'is_member',
        'is_customer',
        'is_supplier',

        'document_type',
        'document_number',

        'name',
        'business_name',
        'first_name',
        'last_name',

        'email',
        'phone',
        'mobile',
        'photo_url',

        'address',
        'ubigeo',

        'birth_date',
        'gender',

        'payment_terms',
        'credit_limit',
        'tax_id',
        'business_license',
        'provider_category',

        'status',
        'notes',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'credit_limit' => 'decimal:2',
        'is_member' => 'boolean',
        'is_customer' => 'boolean',
        'is_supplier' => 'boolean',
    ];

    protected $appends = [
        'display_name',
        'full_name',
    ];

    // ========================================
    // RELATIONSHIPS
    // ========================================

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(MembershipSubscription::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function activeSubscription(): HasOne
    {
        return $this->hasOne(MembershipSubscription::class)
            ->where('status', 'active')
            ->where('end_date', '>=', now()->toDateString())
            ->latest('end_date');
    }

    public function isMember(): bool
    {
        return (bool) $this->is_member;
    }

    public function isCustomer(): bool
    {
        return (bool) $this->is_customer;
    }

    public function isSupplier(): bool
    {
        return (bool) $this->is_supplier;
    }

    public function hasPortalAccess(): bool
    {
        return ! is_null($this->user_id);
    }

    public function getDisplayNameAttribute(): string
    {
        if ($this->business_name) {
            return $this->business_name;
        }

        if ($this->name) {
            return $this->name;
        }

        return mb_trim("{$this->first_name} {$this->last_name}");
    }

    public function getFullNameAttribute(): string
    {
        return mb_trim("{$this->first_name} {$this->last_name}");
    }

    public function scopeMembers($query)
    {
        return $query->where('is_member', true);
    }

    public function scopeCustomers($query)
    {
        return $query->where('is_customer', true);
    }

    public function scopeSuppliers($query)
    {
        return $query->where('is_supplier', true);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeInactive($query)
    {
        return $query->where('status', 'inactive');
    }

    public function scopeSuspended($query)
    {
        return $query->where('status', 'suspended');
    }

    public function scopeWithPortalAccess($query)
    {
        return $query->whereNotNull('user_id');
    }

    public function scopeWithoutPortalAccess($query)
    {
        return $query->whereNull('user_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'is_member',
                'is_customer',
                'is_supplier',
                'document_number',
                'business_name',
                'first_name',
                'last_name',
                'email',
                'phone',
                'status',
                'user_id',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
