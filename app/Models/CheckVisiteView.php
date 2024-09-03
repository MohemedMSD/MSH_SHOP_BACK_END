<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CheckVisiteView extends Model
{
    use HasFactory;

    protected $fillable = ['user_agent', 'ip_adress', 'check_type', 'visited_at'];

}
