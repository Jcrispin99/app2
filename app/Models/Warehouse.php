<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

final class Warehouse extends Model
{
    use LogsActivity;

    protected $fillable = [
        'name',
        'location',
        'company_id',
    ];

    public function inventories(): HasMany
    {
        return $this->hasMany(Inventory::class);
    }

    public function posConfigs(): HasMany
    {
        return $this->hasMany(PosConfig::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'location', 'company_id'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
