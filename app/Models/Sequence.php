<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Sequence extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'sequence_size',
        'step',
        'next_number',
    ];

    protected $casts = [
        'sequence_size' => 'integer',
        'step' => 'integer',
        'next_number' => 'integer',
    ];

    public function journals(): HasMany
    {
        return $this->hasMany(Journal::class);
    }
}
