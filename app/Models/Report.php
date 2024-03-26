<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Report extends Model
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
    public function uploads()
    {
        return $this->hasMany(ReportUpload::class, 'report_id', 'id');
    }
}
