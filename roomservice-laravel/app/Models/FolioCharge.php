<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FolioCharge extends Model
{
    protected $guarded = [];
    
    protected $casts = [
        'posted_to_frontdesk' => 'boolean', 
        'posted_at' => 'datetime'
    ];

    // IDINAGDAG: Relationship papunta sa Order
    public function order() {
        return $this->belongsTo(Order::class);
    }
}