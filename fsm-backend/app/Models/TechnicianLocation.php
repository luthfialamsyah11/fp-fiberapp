<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TechnicianLocation extends Model
{
    protected $fillable = ['technician_id', 'latitude', 'longitude'];

    public function technician()
    {
        return $this->belongsTo(User::class, 'technician_id');
    }
}