<?php

namespace App\Models;

use Carbon\Carbon;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Model;
use Cviebrock\EloquentSluggable\Sluggable;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Agenda extends Model
{
    use Sluggable, LogsActivity, SoftDeletes, HasUuids;

    protected $table = 'agendas';

    protected $fillable = [
        'title',
        'slug',
        'description',
        'date'
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
            ->useLogName('agenda')
            ->logOnly(['title', 'description', 'date'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function getDateAttribute($value)
    {
        return $value ? Carbon::parse($value)->translatedFormat('d F Y') : null;
    }

    public function getCreatedAtAttribute($value)
    {
        return $value ? Carbon::parse($value)->translatedFormat('d F Y') : null;
    }
}
