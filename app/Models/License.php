<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
class License extends Model
{
    use HasUuids, HasFactory, SoftDeletes;
    protected $hidden = [
        'one_month_before_expiration',
        'two_weeks_before_expiration',
        'three_days_before_expiration',
        'expiry_alert_sent'
    ];
    public function scopeSearch($query, $keyword)
    {
        // return $query->where('name', 'LIKE', "%$search%");
        return $query->where(function ($q) use ($keyword) {
            $q->where('license_no',  'LIKE', '%'.$keyword.'%')
            ->orWhereHas('client', function ($q) use ($keyword) {
                $q->where('company_name', 'LIKE', '%' . $keyword . '%');
            })
            ->orWhereHas('subsidiary', function ($q) use ($keyword) {
                $q->where('name', 'LIKE', '%' . $keyword . '%');
            })
            ->orWhereHas('licenseType', function ($q) use ($keyword) {
                // $q->where('name', 'LIKE', '%' . $keyword . '%');
                $q->orWhere('slug', 'LIKE', '%' . $keyword . '%');
            })
            ->orWhereHas('mineral', function ($q) use ($keyword) {
                $q->where('name', 'LIKE', '%' . $keyword . '%');
            })
            ->orWhereHas('state', function ($q) use ($keyword) {
                $q->where('name', 'LIKE', '%' . $keyword . '%');
            })
            ->orWhereHas('lga', function ($q) use ($keyword) {
                $q->where('name', 'LIKE', '%' . $keyword . '%');
            });
        });
    }
    public function certificates()
    {
        return $this->hasMany(Renewal::class, 'license_id', 'id');
    }
    public function reports()
    {
        return $this->hasMany(Report::class, 'license_id', 'id');
    }
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
