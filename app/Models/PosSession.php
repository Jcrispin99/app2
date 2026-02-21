<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

final class PosSession extends Model
{
    use HasFactory, LogsActivity;

    public const STATUS_OPENING_CONTROL = 'opening_control';

    public const STATUS_OPENED = 'opened';

    public const STATUS_CLOSING_CONTROL = 'closing_control';

    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'user_id',
        'pos_config_id',
        'opening_balance',
        'opening_note',
        'closing_balance',
        'closing_note',
        'opened_at',
        'closed_at',
        'status',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'closing_balance' => 'decimal:2',
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function posConfig(): BelongsTo
    {
        return $this->belongsTo(PosConfig::class);
    }

    public function payments()
    {
        return $this->hasMany(PosSessionPayment::class);
    }

    public function isOpen(): bool
    {
        return in_array($this->status, [self::STATUS_OPENING_CONTROL, self::STATUS_OPENED, self::STATUS_CLOSING_CONTROL]);
    }

    public function isClosed(): bool
    {
        return $this->status === self::STATUS_CLOSED;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'user_id',
                'pos_config_id',
                'opening_balance',
                'closing_balance',
                'status',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
