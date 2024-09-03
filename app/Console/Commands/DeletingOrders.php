<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use App\Models\Order;
use App\Http\Controllers\ProductController;

class DeletingOrders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:deleting-orders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete Not completed Orders';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        //
    

        $orders = Order::where('paid', 0)
        ->where('created_at', '<', now()->subMinutes(30))->get();

        if ($orders->isNotEmpty()) {

            foreach($orders as $order_sele) {

                $order = Order::where('session_id', $order_sele->session_id)->first();
                $orderProducts = $order->products;
                
                $order->notifications()->where('response', '')->delete();
                DB::table('orders')->where('id', $order->id)->delete(); // الآن المتغير $order يشير إلى الكائن الصحيح

            }

        }


    }
}
