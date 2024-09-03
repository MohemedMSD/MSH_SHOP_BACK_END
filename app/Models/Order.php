<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'total_price', 
        'quantity', 
        'order_status_id', 
        'received', 
        'session_id', 
        'paid',
        'adress'
    ];

    protected $casts = [
        'adress' => 'json'
    ];


    public function user(){
        return $this->belongsTo(User::class);
    }

    public function products(){
        return $this->belongsToMany(Product::class)->withPivot(['quantity', 'price']);
    }

    public function notifications(){
        return $this->hasMany(Notification::class);
    }

    public function archive(){
        return $this->hasOne(Archive_order::class);
    }

}
