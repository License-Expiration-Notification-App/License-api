<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
class ReportUpload extends Model
{
    use HasUuids, HasFactory;
    public function report()
    {
        return $this->belongsTo(Report::class);
    }
}
