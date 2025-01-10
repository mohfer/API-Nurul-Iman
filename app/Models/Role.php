<?php

namespace App\Models;

use Spatie\Activitylog\LogOptions;
use Spatie\Permission\Models\Role as SpatieRole;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Traits\LogsActivity;

class Role extends SpatieRole
{
    use HasFactory, HasUuids, LogsActivity;

    protected $primaryKey = 'uuid';

    protected $fillable = [
        'name'
    ];

    protected $guard_name = ['sanctum', 'roles'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('role')
            ->logOnly(['name'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
