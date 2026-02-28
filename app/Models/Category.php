<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

final class Category extends Model
{
    use SoftDeletes;
    use LogsActivity;

    protected $fillable = [
        'name',
        'full_name',
        'description',
        'parent_id',
        'is_active',
    ];

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function products()
    {
        return $this->hasMany(ProductTemplate::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'name',
                'full_name',
                'description',
                'parent_id',
                'is_active',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName) => "Category {$eventName}");
    }

    public function generateFullName()
    {
        if ($this->parent_id) {
            $parent = self::find($this->parent_id);

            return $parent ? "{$parent->full_name} / {$this->name}" : $this->name;
        }

        return $this->name;
    }

    public function updateFullName()
    {
        $this->full_name = $this->generateFullName();
        $this->saveQuietly();

        foreach ($this->children as $child) {
            $child->updateFullName();
        }
    }

    protected static function booted()
    {
        self::saving(function ($category) {
            $category->full_name = $category->generateFullName();
        });

        self::updated(function ($category) {
            if ($category->wasChanged(['name', 'parent_id'])) {
                foreach ($category->children as $child) {
                    $child->updateFullName();
                }
            }
        });
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
