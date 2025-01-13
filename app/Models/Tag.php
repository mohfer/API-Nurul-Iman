<?php

namespace App\Models;

use App\Models\NewsTag;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Model;
use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tag extends Model
{
    use HasFactory, Sluggable, LogsActivity, SoftDeletes, HasUuids;

    protected $table = 'tags';

    protected $fillable = [
        'tag',
        'slug'
    ];

    public function news(): BelongsToMany
    {
        return $this->belongsToMany(News::class, 'news_tags', 'tag_id', 'news_id');
    }

    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => 'tag'
            ]
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('tag')
            ->logOnly(['tag'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
