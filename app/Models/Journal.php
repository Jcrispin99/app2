<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class Journal extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'name',
        'code',
        'type',
        'is_fiscal',
        'document_type_code',
        'sequence_id',
        'company_id',
    ];

    protected $casts = [
        'is_fiscal' => 'boolean',
    ];

    // ========================================
    // RELATIONSHIPS
    // ========================================

    public function sequence(): BelongsTo
    {
        return $this->belongsTo(Sequence::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }
}
