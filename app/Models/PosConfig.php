<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

final class PosConfig extends Model
{
    use SoftDeletes;
    use HasFactory, LogsActivity;

    protected $fillable = [
        'company_id',
        'name',
        'warehouse_id',
        'default_customer_id',
        'tax_id',
        'apply_tax',
        'prices_include_tax',
        'is_active',
    ];

    protected $casts = [
        'apply_tax' => 'boolean',
        'prices_include_tax' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function defaultCustomer(): BelongsTo
    {
        return $this->belongsTo(Partner::class, 'default_customer_id');
    }

    public function tax(): BelongsTo
    {
        return $this->belongsTo(Tax::class);
    }

    public function journals(): BelongsToMany
    {
        return $this->belongsToMany(Journal::class, 'journal_pos_configs')
            ->withPivot(['document_type', 'is_default'])
            ->withTimestamps();
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(PosSession::class);
    }

    public function getJournalsForType(string $documentType)
    {
        return $this->journals()
            ->wherePivot('document_type', $documentType)
            ->get();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'name',
                'warehouse_id',
                'default_customer_id',
                'tax_id',
                'apply_tax',
                'prices_include_tax',
                'is_active',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
