<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Renewal extends Model
{
    use HasFactory, SoftDeletes;
    public function client()
    {
        return $this->belongsTo(Client::class);
    }
    public function subsidiary()
    {
        return $this->belongsTo(Subsidiary::class);
    }
    public function license()
    {
        return $this->belongsTo(License::class);
    }
}
