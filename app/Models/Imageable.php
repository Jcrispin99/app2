<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class Imageable extends Model
{
    protected $fillable = ['path', 'size'];

    public function imageable()
    {
        return $this->morphTo();
    }
}
