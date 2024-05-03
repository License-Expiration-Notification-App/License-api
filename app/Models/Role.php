<?php

namespace App\Models;

use Laratrust\Models\Role as RoleModel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
class Role extends RoleModel
{
    use HasUuids;
    public $guarded = [];
    protected $fillable = ['name', 'display_name', 'description'];
}
