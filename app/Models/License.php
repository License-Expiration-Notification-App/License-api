<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
class License extends Model
{
    use HasUuids, HasFactory, SoftDeletes;
    public function client()
    {
        return $this->belongsTo(Client::class);
    }
    public function subsidiary()
    {
        return $this->belongsTo(Subsidiary::class);
    }
    public function licenseType()
    {
        return $this->belongsTo(LicenseType::class);
    }
    public function mineral()
    {
        return $this->belongsTo(Mineral::class);
    }
    public function state()
    {
        return $this->belongsTo(State::class);
    }
    public function lga()
    {
        return $this->belongsTo(LocalGovernmentArea::class, 'lga_id', 'id');
    }
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'added_by', 'id');
    }
}
