<?php

namespace App\Models;

use App\Models\User;
use App\Models\NewsTag;
use App\Models\Category;
use Illuminate\Database\Eloquent\Model;
use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class News extends Model
{
    use Sluggable;

    protected $table = 'news';

    protected $fillable = [
        'title',
        'slug',
        'thumbnail',
        'content',
        'user_id',
        'category_id',
        'is_published',
        'published_at'
    ];

    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }

    public function category(): HasOne
    {
        return $this->hasOne(Category::class, 'id', 'category_id');
    }

    public function news_tags(): HasMany
    {
        return $this->hasMany(NewsTag::class);
    }

    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => 'title'
            ]
        ];
    }
}
