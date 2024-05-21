<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
class Report extends Model
{
    use HasUuids, HasFactory, SoftDeletes;
    protected $fillable = ['client_id','subsidiary_id','license_id','report_type','due_date'];
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
    public function uploads()
    {
        return $this->hasMany(ReportUpload::class, 'report_id', 'id');
    }
}
