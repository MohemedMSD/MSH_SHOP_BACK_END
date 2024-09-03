<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Archive_order extends Model
{
    use HasFactory;
    protected $fillable = [
        'ref', 
        'session_id', 
        'part', 
        'received', 
        'user_id', 
        'status', 
        'quantity', 
        'total_price', 
        'products', 
        'order_id', 
        'email',
        'adress'
    ];
    
    protected $casts = [
        'adress' => 'json'
    ];

    public function user(){
        return $this->belongsTo(User::class);
    }

    public function order(){
        return $this->belongsTo(Order::class);
    }
}
