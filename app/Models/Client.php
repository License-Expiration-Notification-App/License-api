<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    use HasFactory, SoftDeletes;
    public function subsidiaries()
    {
        return $this->hasMany(Subsidiary::class);
    }
    public function licenses()
    {
        return $this->hasMany(License::class);
    }
    public function users()
    {
        return $this->belongsToMany(User::class);
    }
}
