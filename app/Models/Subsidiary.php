<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subsidiary extends Model
{
    use HasFactory, SoftDeletes;
    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
