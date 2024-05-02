<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Uuid;
class Subsidiary extends Model
{
    use Uuid,HasFactory, SoftDeletes;
    public function client()
    {
        return $this->belongsTo(Client::class);
    }
    public function licenses()
    {
        return $this->hasMany(License::class);
    }
}
