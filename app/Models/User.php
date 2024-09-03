<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use App\Models\Role;
use App\Models\Payment_Shipping;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
        'adress',
        'profile'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'adress' => 'json'
    ];

    public function role(){
        return $this->belongsTo(Role::class);
    }

    public function orders(){
        return $this->hasMany(Order::class);
    }
    
    public function notifications(){
        return $this->hasMany(Notification::class);
    }
    
    public function card(){
        return $this->hasMany(Card::class);
    }

    public function archiveOrders(){
        return $this->hasMany(Archive_order::class);
    }

    public function reviews(){
        return $this->hasMany(Review::class);
    }
}
