<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
class Mineral extends Model
{
    use HasUuids, HasFactory, SoftDeletes;
    protected $fillable = ['name'];
    public function licenses()
    {
        return $this->hasMany(License::class);
    }
}
