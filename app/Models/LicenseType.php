<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
class LicenseType extends Model
{
    use HasUuids, HasFactory, SoftDeletes;
    public function licenses()
    {
        return $this->hasMany(License::class);
    }
}
