<?php

namespace App\Models;

use App\Models\NewsTag;
use Illuminate\Database\Eloquent\Model;
use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tag extends Model
{
    use Sluggable;

    public $timestamps = false;

    protected $table = 'tags';

    protected $fillable = [
        'tag',
        'slug'
    ];

    public function news_tags(): HasMany
    {
        return $this->hasMany(NewsTag::class, 'tag_id');
    }

    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => 'tag'
            ]
        ];
    }
}
