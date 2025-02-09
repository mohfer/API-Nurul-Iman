<?php

namespace App\Models;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Category;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Model;
use Cviebrock\EloquentSluggable\Sluggable;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class News extends Model
{
    use HasFactory, Sluggable, LogsActivity, SoftDeletes, HasUuids;

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

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'news_tags', 'news_id', 'tag_id');
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

    public function getPublishedAtAttribute($value)
    {
        return $value ? Carbon::parse($value)->translatedFormat('d F Y') : null;
    }

    public function getCreatedAtAttribute($value)
    {
        return $value ? Carbon::parse($value)->translatedFormat('d F Y') : null;
    }
}
