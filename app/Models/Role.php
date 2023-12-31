<?php

namespace App\Models;

use Spatie\Activitylog\Traits\LogsActivity;

class Role extends \Spatie\Permission\Models\Role
{
    use  LogsActivity;

    protected static $logAttributes = ['*'];

    protected static $logOnlyDirty = true;

    public bool $inPermission = true;

    protected $fillable = ['name', 'display_name', 'guard_name'];
}
