<?php

namespace App\Models;

use App\Models\News;
use Illuminate\Database\Eloquent\Model;
use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use Sluggable;

    public $timestamps = false;

    protected $table = 'categories';

    protected $fillable = [
        'category',
        'slug'
    ];

    public function news(): HasMany
    {
        return $this->hasMany(News::class, 'category_id');
    }

    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => 'category'
            ]
        ];
    }
}
