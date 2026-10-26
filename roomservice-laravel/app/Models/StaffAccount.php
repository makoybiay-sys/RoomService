<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class StaffAccount extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'employee_id', 'username', 'password', 'full_name', 'role', 'area', 'is_active', 'last_login'
    ];

    protected $hidden = ['password'];
    protected $casts = ['is_active' => 'boolean', 'last_login' => 'datetime'];

    public function ordersAsWaiter() {
        return $this->hasMany(Order::class, 'waiter_id');
    }
}