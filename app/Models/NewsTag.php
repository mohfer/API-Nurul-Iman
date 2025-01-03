<?php

namespace App\Models;

use App\Models\Tag;
use App\Models\News;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NewsTag extends Pivot
{
    use LogsActivity, HasUuids;

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

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('news_tag')
            ->logOnly(['news_id', 'tag_id'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public $incrementing = true;
}
