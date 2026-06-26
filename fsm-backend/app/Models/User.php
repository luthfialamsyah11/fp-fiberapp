<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'role', 'phone', 'is_active', 'is_online'
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'is_active' => 'boolean',
        'is_online' => 'boolean',
    ];

    public function tasks()
    {
        return $this->hasMany(Task::class, 'technician_id');
    }

    public function locations()
    {
        return $this->hasMany(TechnicianLocation::class, 'technician_id');
    }

    public function latestLocation()
    {
        return $this->hasOne(TechnicianLocation::class, 'technician_id')->latestOfMany();
    }
}