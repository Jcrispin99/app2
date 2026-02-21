<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Attendance extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'partner_id',
        'membership_subscription_id',
        'company_id',
        'check_in_time',
        'check_out_time',
        'duration_minutes',
        'status',
        'validation_message',
        'is_manual_entry',
        'registered_by',
    ];

    protected $casts = [
        'check_in_time' => 'datetime',
        'check_out_time' => 'datetime',
        'is_manual_entry' => 'boolean',
    ];

    // ========================================
    // RELATIONSHIPS
    // ========================================

    /**
     * Get the partner that made this attendance
     */
    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    /**
     * Get the subscription used for this attendance
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(MembershipSubscription::class, 'membership_subscription_id');
    }

    /**
     * Get the company
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the staff member who registered this attendance (if manual)
     */
    public function registeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by');
    }

    // ========================================
    // SCOPES
    // ========================================

    /**
     * Scope to get valid attendances
     */
    public function scopeValid($query)
    {
        return $query->where('status', 'valid');
    }

    /**
     * Scope to get denied attendances
     */
    public function scopeDenied($query)
    {
        return $query->where('status', 'denied');
    }

    /**
     * Scope to get attendances for today
     */
    public function scopeToday($query)
    {
        return $query->whereDate('check_in_time', Carbon::today());
    }

    /**
     * Scope by company
     */
    public function scopeByCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * Scope by partner
     */
    public function scopeByPartner($query, $partnerId)
    {
        return $query->where('partner_id', $partnerId);
    }

    /**
     * Scope for date range
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('check_in_time', [$startDate, $endDate]);
    }

    // ========================================
    // HELPER METHODS
    // ========================================

    /**
     * Calculate and update duration when checking out
     */
    public function checkOut(): void
    {
        $this->check_out_time = Carbon::now();
        $this->duration_minutes = $this->check_in_time->diffInMinutes($this->check_out_time);
        $this->save();
    }

    /**
     * Check if attendance is still active (no check-out)
     */
    public function isActive(): bool
    {
        return $this->check_out_time === null;
    }

    /**
     * Get formatted duration
     */
    public function getFormattedDuration(): ?string
    {
        if (! $this->duration_minutes) {
            return null;
        }

        $hours = floor($this->duration_minutes / 60);
        $minutes = $this->duration_minutes % 60;

        if ($hours > 0) {
            return "{$hours}h {$minutes}m";
        }

        return "{$minutes}m";
    }
}
