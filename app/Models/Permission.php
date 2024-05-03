<?php

namespace App\Models;

use Laratrust\Models\Permission as PermissionModel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
class Permission extends PermissionModel
{
    use HasUuids;
    public $guarded = [];
}
