<?php

namespace App\Models;

class Permission extends \Spatie\Permission\Models\Permission
{
    public bool $inPermission = true;
    protected $fillable = ['name', 'display_name', 'group' , 'guard_name'];
}
