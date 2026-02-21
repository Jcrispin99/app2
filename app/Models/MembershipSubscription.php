<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use DomainException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class MembershipSubscription extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'partner_id',
        'membership_plan_id',
        'company_id',
        'start_date',
        'end_date',
        'original_end_date',
        'amount_paid',
        'payment_method',
        'payment_reference',
        'entries_used',
        'last_entry_date',
        'entries_this_month',
        'current_month_start',
        'total_days_frozen',
        'remaining_freeze_days',
        'status',
        'notes',
        'sold_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'original_end_date' => 'date',
        'last_entry_date' => 'date',
        'current_month_start' => 'date',
        'amount_paid' => 'decimal:2',
    ];

    // Relationships
    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(MembershipPlan::class, 'membership_plan_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function soldBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sold_by');
    }

    public function freezes(): HasMany
    {
        return $this->hasMany(MembershipFreeze::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where('end_date', '>=', now()->toDateString());
    }

    public function scopeFrozen($query)
    {
        return $query->where('status', 'frozen');
    }

    public function scopeExpired($query)
    {
        return $query->where('status', 'expired')
            ->orWhere(function ($q) {
                $q->where('status', 'active')
                    ->where('end_date', '<', now()->toDateString());
            });
    }

    public function scopeByCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeByPartner($query, $partnerId)
    {
        return $query->where('partner_id', $partnerId);
    }

    // Helper Methods
    public function isActive(): bool
    {
        return $this->status === 'active' && $this->end_date->isFuture();
    }

    public function isExpired(): bool
    {
        return $this->status === 'expired' ||
               ($this->status === 'active' && $this->end_date->isPast());
    }

    public function isFrozen(): bool
    {
        return $this->status === 'frozen';
    }

    public function canEntry(): bool
    {
        $this->applyScheduledFreezeIfNeeded();

        if (! $this->isActive()) {
            return false;
        }

        // Reset monthly counter if needed
        $this->resetMonthlyCounterIfNeeded();

        // Check monthly limit
        if (! is_null($this->plan->max_entries_per_month)) {
            if ($this->entries_this_month >= $this->plan->max_entries_per_month) {
                return false;
            }
        }

        // Check daily limit
        if ($this->last_entry_date?->isToday()) {
            // Ya entró hoy, verificar límite diario
            $entriesToday = $this->getEntriesToday();
            if ($entriesToday >= $this->plan->max_entries_per_day) {
                return false;
            }
        }

        return true;
    }

    public function getDaysRemaining(): int
    {
        if ($this->end_date->isPast()) {
            return 0;
        }

        return now()->diffInDays($this->end_date, false);
    }

    public function getProgress(): float
    {
        $total = $this->start_date->diffInDays($this->original_end_date);
        $used = $this->start_date->diffInDays(now());

        if ($total === 0) {
            return 100;
        }

        return min(100, ($used / $total) * 100);
    }

    public function recordEntry(): void
    {
        $this->resetMonthlyCounterIfNeeded();

        $this->increment('entries_used');
        $this->increment('entries_this_month');
        $this->last_entry_date = now();
        $this->save();
    }

    public function resetMonthlyCounterIfNeeded(): void
    {
        if (! $this->current_month_start || ! $this->current_month_start->isSameMonth(now())) {
            $this->entries_this_month = 0;
            $this->current_month_start = now()->startOfMonth();
            $this->save();
        }
    }

    /**
     * Get all attendances for this subscription
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function extendEndDate(int $days): void
    {
        $this->end_date = $this->end_date->addDays($days);
        $this->save();
    }

    public function freeze(int $days, ?string $reason = null): bool
    {
        if (! $this->plan->allows_freezing) {
            return false;
        }

        if ($days > $this->remaining_freeze_days) {
            return false;
        }

        $this->status = 'frozen';
        $this->remaining_freeze_days -= $days;
        $this->save();

        return true;
    }

    public function unfreeze(): void
    {
        $this->status = 'active';
        $this->save();
    }

    public function freezeWithDates(Carbon $freezeStartDate, Carbon $freezeEndDate, ?string $reason = null, ?int $userId = null): MembershipFreeze
    {
        if (! in_array($this->status, ['active'])) {
            throw new DomainException('Solo se pueden congelar suscripciones activas.');
        }

        if (! $this->plan->allows_freezing) {
            throw new DomainException('Este plan no permite congelamiento.');
        }

        if ($this->freezes()->where('status', 'active')->exists()) {
            throw new DomainException('Ya existe un congelamiento activo para esta suscripción.');
        }

        $freezeStartDate = $freezeStartDate->copy()->startOfDay();
        $freezeEndDate = $freezeEndDate->copy()->startOfDay();
        $plannedDays = $freezeStartDate->diffInDays($freezeEndDate);

        if ($plannedDays < 1) {
            throw new DomainException('El rango de congelamiento debe ser de al menos 1 día.');
        }

        if ($plannedDays > $this->remaining_freeze_days) {
            throw new DomainException("Solo quedan {$this->remaining_freeze_days} días de congelamiento disponibles.");
        }

        $this->remaining_freeze_days -= $plannedDays;
        $this->save();

        if ($freezeStartDate->lessThanOrEqualTo(now()->startOfDay())) {
            $this->end_date = $this->end_date->copy()->addDays($plannedDays);
            $this->status = 'frozen';
            $this->save();
        }

        return $this->freezes()->create([
            'freeze_start_date' => $freezeStartDate,
            'freeze_end_date' => $freezeEndDate,
            'days_frozen' => 0,
            'planned_days' => $plannedDays,
            'reason' => $reason,
            'requested_by' => $userId,
            'approved_by' => $userId,
            'status' => 'active',
        ]);
    }

    public function applyScheduledFreezeIfNeeded(): void
    {
        if ($this->status !== 'active') {
            return;
        }

        $today = now()->startOfDay();

        $freeze = $this->freezes()
            ->where('status', 'active')
            ->whereDate('freeze_start_date', '<=', $today->toDateString())
            ->whereDate('freeze_end_date', '>', $today->toDateString())
            ->latest('id')
            ->first();

        if (! $freeze) {
            return;
        }

        $this->end_date = $this->end_date->copy()->addDays((int) $freeze->planned_days);
        $this->status = 'frozen';
        $this->save();
    }

    protected function getEntriesToday(): int
    {
        return $this->attendances()
            ->valid()
            ->whereDate('check_in_time', Carbon::today())
            ->count();
    }
}
