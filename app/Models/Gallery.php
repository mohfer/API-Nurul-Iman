<?php

namespace App\Models;

use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Spatie\Activitylog\Traits\LogsActivity;

class Gallery extends Model
{
    use LogsActivity, HasUuids;

    protected $table = 'galleries';

    protected $fillable = [
        'title',
        'image_url',
        'description'
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('gallery')
            ->logOnly(['title', 'image_url', 'description'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
