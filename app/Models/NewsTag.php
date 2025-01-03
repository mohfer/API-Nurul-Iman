<?php

namespace App\Models;

use App\Models\Tag;
use App\Models\News;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NewsTag extends Model
{
    protected $table = 'news_tags';

    protected $fillable = [
        'news_id',
        'tag_id'
    ];

    public function news(): BelongsTo
    {
        return $this->belongsTo(News::class, 'news_id');
    }

    public function tag(): BelongsTo
    {
        return $this->belongsTo(Tag::class, 'tag_id');
    }
}
