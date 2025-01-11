<?php

namespace App\Models;

use Carbon\Carbon;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Model;
use Cviebrock\EloquentSluggable\Sluggable;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Announcement extends Model
{
    use Sluggable, LogsActivity, SoftDeletes, HasUuids;

    protected $table = 'announcements';

    protected $fillable = [
        'title',
        'slug',
        'description'
    ];

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
            ->useLogName('announcement')
            ->logOnly(['title', 'description'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function getCreatedAtAttribute($value)
    {
        return Carbon::parse($value)->translatedFormat('d F Y');
    }
}
