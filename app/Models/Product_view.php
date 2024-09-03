<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product_view extends Model
{
    use HasFactory;
    public $table = 'products_views';

    protected $fillable = ['product_id', 'duration', 'count'];

    public function product(){
        return $this->belongsTo(Product::class);
    }
}
