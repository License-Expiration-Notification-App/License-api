<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LicenseActivity extends Model
{
    use HasFactory, HasUuids;
    protected $fillable = [
        'client_id',
        'license_id',
        'title',
        'description',
        'status',
        'due_date',
        'color_code',
        'uuid',
        'type',
        'to_be_reviewed',
    ];
}
