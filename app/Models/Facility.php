<?php

namespace App\Models;

use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Facility extends Model
{
    use LogsActivity, HasUuids, SoftDeletes;

    protected $table = 'facilities';

    protected $fillable = [
        'title',
        'image_url',
        'image_name',
        'description',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('gallery')
            ->logOnly([
                'title',
                'image_url',
                'description'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
