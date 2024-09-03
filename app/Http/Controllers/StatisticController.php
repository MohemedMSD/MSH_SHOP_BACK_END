<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Archive_order;
use App\Models\Product;
use App\Models\Category;
use App\Models\Visite;
use App\Models\Product_view;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class StatisticController extends Controller
{
    //

    public function __construct(){
        $this->middleware('role:seller')->except([]);
    }

    public function Profits($current_year){

        $validation = Validator::make([
            'current_year' => $current_year,
        ], [
            'current_year' => 'required|date_format:Y-m-d',
        ]);

        if ($validation->fails()) {
            return response()->json($validation->messages(), 422);
        }

        $currentYear = Carbon::parse($current_year)->year;

        // Fetch visites for the current year and group by month
        $OrderData = Archive_order::select(
            DB::raw('DATE_FORMAT(created_at, "%b") as month'),
            DB::raw('products')
        )
        ->whereYear('created_at', Carbon::parse('11-08-2024')->year)
        ->orderBy('month')
        ->get()
        ->groupBy('month')
        ->map(function($items, $month) {
            $totalProfit = 0;
            $totalPrice  = 0;
            $totalPurchase  = 0;
        
            foreach($items as $item) {
                $products = json_decode($item->products);
                foreach($products as $product) {
                    $totalProfit += $product->profit;
                    $totalPrice += $product->price;
                    $totalPurchase += $product->price - $product->profit;
                }
            }
        
            return [
                'month' => $month,
                'price' => $totalPrice,
                'profit' => $totalProfit,
                'purchases' => $totalPurchase
            ];

        })->values();
        

        // Initialize an array with all months set to 0
        $monthlyOrder = [];
        for ($month = 1; $month <= 12; $month++) {
            $monthYear = Carbon::createFromDate(null, $month, 1)->format('M');
            $monthlyOrder[$monthYear] = 0;
        }

        // Populate the array with actual data
        foreach ($OrderData as $order) {
            $monthlyOrder[$order['month']] = [
                'profit' => $order['profit'],
                'price' => $order['price'],
                'expenses' => $order['purchases']
            ];
        }

        $data = [$monthlyOrder];

        return response()->json($data);

    }

    public function home(){

        $products = Product::all()->count();

        $ordersConfirmed = Archive_Order::whereYear('created_at', now()->year)
        ->whereMonth('created_at', now()->month)
        ->where('received', 1)
        ->count();

        $ordersNotConfirmed = Archive_Order::whereYear('created_at', now()->year)
        ->whereMonth('created_at', now()->month)
        ->where('received', 0)
        ->count();

        $ordersMoney = Archive_Order::whereYear('created_at', now()->year)
        ->whereMonth('created_at', now()->month)
        ->get('products')->map(function($item, $index = 0){
            $products = json_decode($item->products);
            $profit = 0;
            foreach($products as $product){
                $profit += $product->profit;
            }
            $index+=1;
            return $profit;
        })->sum();

        $categories = Category::all()->count();

        return response()->json([
            'products' => $products,
            'orders_confirmed' => $ordersConfirmed,
            'orders_not_confirmed' => $ordersNotConfirmed,
            'categories' => $categories,
            'total_money' => $ordersMoney
        ]);

    }
    
    public function orders($current_year){

            $validation = Validator::make([
                'current_year' => $current_year,
            ], [
                'current_year' => 'required|date_format:Y-m-d',
            ]);

            if ($validation->fails()) {
                return response()->json($validation->messages(), 422);
            }

            $currentYear = Carbon::parse($current_year)->year;

            // Fetch orders for the current year and group by month
            $orders = Archive_order::select(
                DB::raw('DATE_FORMAT(created_at, "%b") as month'),
                DB::raw('count(*) as orders_count')
            )
            ->whereYear('created_at', $current_year)
            ->groupBy(DB::raw('DATE_FORMAT(created_at, "%b")'))
            ->orderBy('month')
            ->get();
            

            // Initialize an array with all months set to 0
            $monthlyOrders = [];
            for ($month = 1; $month <= 12; $month++) {
                $monthYear = Carbon::createFromDate(null, $month, 1)->format('M');
                $monthlyOrders[$monthYear] = 0;
            }

            // Populate the array with actual data
            foreach ($orders as $order) {
                $monthlyOrders[$order->month] = $order->orders_count;
            }

            $data = [$monthlyOrders];

            
        return response()->json($data);

    }

    public function orders_amount($current_year){

            $validation = Validator::make([
                'current_year' => $current_year,
            ], [
                'current_year' => 'required|date_format:Y-m-d',
            ]);

            if ($validation->fails()) {
                return response()->json($validation->messages(), 422);
            }

            $currentYear = Carbon::parse($current_year)->year;

            // Fetch orders for the current year and group by month
            $orders = Archive_order::select(
                DB::raw('DATE_FORMAT(created_at, "%b") as month'),
                DB::raw('sum(total_price) as orders_amount')
            )
            ->whereYear('created_at', $current_year)
            ->groupBy(DB::raw('DATE_FORMAT(created_at, "%b")'))
            ->orderBy('month')
            ->get();
            

            // Initialize an array with all months set to 0
            $monthlyOrders = [];
            for ($month = 1; $month <= 12; $month++) {
                $monthYear = Carbon::createFromDate(null, $month, 1)->format('M');
                $monthlyOrders[$monthYear] = 0;
            }

            // Populate the array with actual data
            foreach ($orders as $order) {
                $monthlyOrders[$order->month] = $order->orders_amount;
            }

            $data = [$monthlyOrders];

            
        return response()->json($data);

    }

    public function visites($current_year){
        
        $validation = Validator::make([
            'current_year' => $current_year,
        ], [
            'current_year' => 'required|date_format:Y-m-d',
        ]);

        if ($validation->fails()) {
            return response()->json($validation->messages(), 422);
        }

        $currentYear = Carbon::parse($current_year)->year;

        // Fetch visites for the current year and group by month
        $visites = Visite::select(
            DB::raw('DATE_FORMAT(duration, "%b") as month'),
            DB::raw('sum(count) as visites')
        )
        ->whereYear('duration', $current_year)
        ->groupBy(DB::raw('DATE_FORMAT(duration, "%b")'))
        ->orderBy('month')
        ->get();
        

        // Initialize an array with all months set to 0
        $monthlyVisites = [];
        for ($month = 1; $month <= 12; $month++) {
            $monthYear = Carbon::createFromDate(null, $month, 1)->format('M');
            $monthlyVisites[$monthYear] = 0;
        }

        // Populate the array with actual data
        foreach ($visites as $visite) {
            $monthlyVisites[$visite->month] = $visite->visites;
        }

        $data = [$monthlyVisites];

        return response()->json($data);

    }

    public function product_views($current_year){

        $validation = Validator::make([
            'current_year' => $current_year,
        ], [
            'current_year' => 'required|date_format:Y-m-d',
        ]);

        if ($validation->fails()) {
            return response()->json($validation->messages(), 422);
        }

        $currentYear = Carbon::parse($current_year)->year;

        // Fetch visites for the current year and group by month
        $views = Product_view::select(
            DB::raw('DATE_FORMAT(duration, "%b") as month'),
            DB::raw('sum(count) as views')
        )
        ->whereYear('duration', $current_year)
        ->groupBy(DB::raw('DATE_FORMAT(duration, "%b")'))
        ->orderBy('month')
        ->get();
        

        // Initialize an array with all months set to 0
        $monthlyViews = [];
        for ($month = 1; $month <= 12; $month++) {
            $monthYear = Carbon::createFromDate(null, $month, 1)->format('M');
            $monthlyViews[$monthYear] = 0;
        }

        // Populate the array with actual data
        foreach ($views as $view) {
            $monthlyViews[$view->month] = $view->views;
        }

        $data = [$monthlyViews];

        return response()->json($data);

    }

    public function categories_views($current_date, $type){

        $validation = Validator::make([
            'current_date' => $current_date,
            'type' => isset($type) && ($type == 'month' || $type == 'year') ? $type : null 
        ], [
            'current_date' => 'required|date_format:Y-m-d',
            'type' => 'required'
        ]);

        if ($validation->fails()) {
            return response()->json($validation->messages(), 422);
        }

        $currentDate = Carbon::parse($current_date);

        // Fetch visites for the current year and group by month
        $viewsQuery = Product_view::join('products', 'products.id', '=', 'products_views.product_id')
        ->join('categories', 'categories.id', '=', 'products.category_id')
        ->select(
            DB::raw('sum(products_views.count) as views'),
            'categories.name',
        )
        ->groupBy('categories.name');

        if ($type == 'month') {
            
            $viewsQuery
            ->whereMonth('products_views.duration', $currentDate->month);

        }else if($type == 'year'){

            $viewsQuery->whereYear('products_views.duration', $currentDate->year);

        }

        $views = $viewsQuery->get();

        return response()->json($views);

    }

}
