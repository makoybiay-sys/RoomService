<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $guarded = [];

    protected $casts = [
        'assigned_at' => 'datetime',
        'delivered_at' => 'datetime',
        'arrived_at' => 'datetime',
        'cancel_requested_at' => 'datetime',
        'voided_at' => 'datetime',
        'waiter_arrived' => 'boolean',
        'arrival_seen_by_guest' => 'boolean',
    ];

    public function items() {
        return $this->hasMany(OrderItem::class);
    }

    public function waiter() {
        return $this->belongsTo(StaffAccount::class, 'waiter_id');
    }
    
    public function coffeeManager() {
        return $this->belongsTo(StaffAccount::class, 'coffee_manager_id');
    }

    public function folioCharge() {
        return $this->hasOne(FolioCharge::class);
    }
}