<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

final class Purchase extends Model
{
    use SoftDeletes;
    use LogsActivity;

    protected $fillable = [
        'serie',
        'correlative',
        'journal_id',
        'date',
        'partner_id',
        'warehouse_id',
        'company_id',
        'total',
        'observation',
        'status',
        'payment_status',
        'vendor_bill_number',
        'vendor_bill_date',
    ];

    protected $casts = [
        'date' => 'datetime',
        'vendor_bill_date' => 'date',
        'total' => 'decimal:2',
    ];

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function productables(): MorphMany
    {
        return $this->morphMany(Productable::class, 'productable');
    }

    public function inventories(): MorphMany
    {
        return $this->morphMany(Inventory::class, 'inventoryable');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopePosted($query)
    {
        return $query->where('status', 'posted');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    public function scopeUnpaid($query)
    {
        return $query->where('payment_status', 'unpaid');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'status',
                'partner_id',
                'warehouse_id',
                'total',
                'observation',
                'payment_status',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
