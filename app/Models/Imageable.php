<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Imageable extends Model
{
    use SoftDeletes;
    protected $fillable = ['path', 'size'];

    public function imageable()
    {
        return $this->morphTo();
    }
}
