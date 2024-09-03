<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = [
        'name', 'description', 'discount', 'images', 'price', 'purchase_price', 'quantity', 'category_id', 'discount_id'
    ];

    protected $casts = [
        'images' => 'json'
    ];

    public function orders(){
        return $this->belongsToMany(Order::class)->withPivot(['quantity', 'price']);
    }
    
    public function category(){
        return $this->belongsTo(Category::class);
    }

    public function cards(){
        return $this->hasMany(Card::class);
    }
    
    public function Discount(){
        return $this->belongsTo(Discount::class);
    }
    
    public function reviews(){
        return $this->hasMany(Review::class);
    }

    public function views(){
        return $this->hasMany(Product_view::class);
    }
}
