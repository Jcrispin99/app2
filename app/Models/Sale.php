<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

final class Sale extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'serie',
        'correlative',
        'journal_id',
        'date',
        'partner_id',
        'warehouse_id',
        'company_id',
        'original_sale_id',
        'pos_session_id',
        'user_id',
        'subtotal',
        'tax_amount',
        'total',
        'status',
        'payment_status',
        'sunat_status',
        'sunat_response',
        'sunat_sent_at',
        'notes',
    ];

    protected $casts = [
        'date' => 'datetime',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'sunat_response' => 'array',
        'sunat_sent_at' => 'datetime',
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

    public function posSession(): BelongsTo
    {
        return $this->belongsTo(PosSession::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function products(): MorphMany
    {
        return $this->morphMany(Productable::class, 'productable');
    }

    public function originalSale()
    {
        return $this->belongsTo(self::class, 'original_sale_id');
    }

    public function creditNotes()
    {
        return $this->hasMany(self::class, 'original_sale_id');
    }

    public function paymentsUsingThisCredit()
    {
        return $this->hasMany(PosSessionPayment::class, 'reference_sale_id');
    }

    public function inventories(): MorphMany
    {
        return $this->morphMany(Inventory::class, 'inventoryable');
    }

    public function getDocumentNumberAttribute(): string
    {
        return "{$this->serie}-{$this->correlative}";
    }

    public function scopePosted($query)
    {
        return $query->where('status', 'posted');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopePaid($query)
    {
        return $query->where('payment_status', 'paid');
    }

    public function scopeUnpaid($query)
    {
        return $query->where('payment_status', 'unpaid');
    }

    public function scopeForSession($query, $sessionId)
    {
        return $query->where('pos_session_id', $sessionId);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('date', today());
    }

    public function isPosted(): bool
    {
        return $this->status === 'posted';
    }

    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'serie',
                'correlative',
                'journal_id',
                'partner_id',
                'warehouse_id',
                'notes',
                'subtotal',
                'tax_amount',
                'total',
                'status',
                'payment_status',
                'sunat_status',
                'original_sale_id',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->dontLogIfAttributesChangedOnly(['updated_at']);
    }

    protected static function booted(): void
    {
        self::creating(function (self $sale) {
            if (empty($sale->date)) {
                $sale->date = now();
            }
        });
    }
}
