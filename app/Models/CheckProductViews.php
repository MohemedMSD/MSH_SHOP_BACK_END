<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CheckProductViews extends Model
{
    use HasFactory;
    public $table = 'check_products_views';
    protected $fillable = ['user_agent', 'ip_adress', 'visited_at', 'product_id'];
}
