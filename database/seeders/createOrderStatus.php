<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\OrderStatus;

class createOrderStatus extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $status = ['Processing', 'Shipped', 'Delivered', 'Received'];
        $i = 1;
        foreach($status as $s){
            OrderStatus::create([
                'statut' => $s,
                'part' => $i
            ]);
            $i++;
        }
    }
}
