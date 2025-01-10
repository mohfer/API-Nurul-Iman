<?php

namespace App\Models;

use App\Models\User;
use App\Models\NewsTag;
use App\Models\Category;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Model;
use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class News extends Model
{
    use Sluggable, LogsActivity, SoftDeletes, HasUuids;

    protected $table = 'news';

    protected $fillable = [
        'title',
        'slug',
        'image_url',
        'image_name',
        'content',
        'user_id',
        'category_id',
        'is_published',
        'published_at'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function news_tags(): HasMany
    {
        return $this->hasMany(NewsTag::class, 'news_id');
    }

    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => 'title'
            ]
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('news')
            ->logOnly([
                'title',
                'image_url',
                'content',
                'user.name',
                'category.category',
                'is_published',
                'published_at'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
