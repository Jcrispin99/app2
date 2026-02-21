<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

final class Company extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'parent_id',
        'branch_code',
        'is_main',
        'business_name',
        'trade_name',
        'ruc',
        'address',
        'phone',
        'email',
        'ubigeo',
        'active',
    ];

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function scopeInactive($query)
    {
        return $query->where('active', false);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function branches()
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function membershipPlans()
    {
        return $this->hasMany(MembershipPlan::class);
    }

    public function membershipSubscriptions()
    {
        return $this->hasMany(MembershipSubscription::class);
    }

    public function partners()
    {
        return $this->hasMany(Partner::class);
    }

    public function scopeMainOffices($query)
    {
        return $query->whereNull('parent_id')->orWhere('is_main_office', true);
    }

    public function scopeBranches($query)
    {
        return $query->whereNotNull('parent_id');
    }

    public function isBranch(): bool
    {
        return ! is_null($this->parent_id);
    }

    public function isMainOffice(): bool
    {
        return is_null($this->parent_id) || $this->is_main_office;
    }

    public function getAllBranches()
    {
        if ($this->isMainOffice()) {
            return $this->branches;
        }

        return $this->parent?->branches ?? collect([]);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'business_name',
                'trade_name',
                'ruc',
                'address',
                'phone',
                'email',
                'active',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName) => "Company {$eventName}");
    }

    protected function casts(): array
    {
        return [
            'is_main' => 'boolean',
            'active' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
