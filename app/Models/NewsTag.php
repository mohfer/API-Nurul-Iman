<?php

namespace App\Models;

use App\Models\Tag;
use App\Models\News;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class NewsTag extends Model
{
    public $timestamps = false;

    protected $table = 'news_tags';

    protected $fillable = [
        'news_id',
        'tag_id',
    ];

    public function news(): HasOne
    {
        return $this->hasOne(News::class, 'id', 'news_id');
    }

    public function tag(): HasOne
    {
        return $this->hasOne(Tag::class, 'id', 'tag_id');
    }
}
