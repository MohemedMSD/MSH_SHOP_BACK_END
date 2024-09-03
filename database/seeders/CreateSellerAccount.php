<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\User;
use Hash;

class CreateSellerAccount extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //

        $seller = User::create([
            'name' => 'seller',
            'email' => 'seller@gmail.com',
            'password' => Hash::make('12345678'),
            'role_id' => Role::where('name', 'seller')->first()->id
        ]);

        $token = $seller->createToken('auth_token')->accessToken;


    }
}
