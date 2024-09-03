<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Crypt;
use App\Models\Archive_order;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\OrderResource;

class ArchiveOrdersController extends Controller
{
    //

    public function __construct(){
        $this->middleware('role:seller')->only(['index', 'show', 'search']);
    }

    public function index($current_page){

        $validation = Validator::make([
            'current_page' => $current_page
        ], [
            'current_page' => 'integer'
        ]);

        if ($validation->fails()) {
            return response()->json($validation->messages(), 422);
        }

        $itemsPerPage = 10;
        $orders = Archive_order::orderBy('created_at', 'desc')->get();

        return response()->json([
            'orders' => OrderResource::collection($orders->forPage($current_page, $itemsPerPage)),
            'total_pages' => ceil($orders->count()/$itemsPerPage)
        ]);

    }

    public function show($id){
        
        try {

            $id = Crypt::decrypt($id);
                
            $order = Archive_order::find($id);
            
            return response()->json([
                'id' => Crypt::encrypt($order->id),
                'ref' => $order->ref,
                'total_price' => $order->total_price ,
                'quantity' => $order->quantity ,
                'received' => $order->received ,
                'part' => $order->part ,
                'adress' => $order->adress,
                'products' => $order->products ,
                'email' => $order->email ,
                'created_at' => $order->created_at ,
                'order_id' => Crypt::encrypt($order->order_id)
            ]);

        } catch (\Throwable $e) {
            
            return response()->json('Something not correct', 422);

        }

    }

    public function search(Request $request, $query, $start_date, $end_date, $status, $current_page){    

        $validation = Validator::make([
            'current_page' => $current_page,
            'query' => $query != 'null' ? $query : null,
            'start_date' => $start_date != 'null' ? $start_date : null ,
            'end_date' => $end_date != 'null' ? $end_date : null ,
            'status' => $status != 'null' ? $status : null,
        ], [
            'current_page' => 'integer',
            'query' => 'nullable|string|max:225|regex:/^[\w\s\-\_\=\+\%]+$/',
            'date' => 'nullable|regex:/^\d{4}-\d{2}(-\d{2})?$/',
            'statuts' => 'exists:order_status,statut|nullable'
        ]);

        if ($validation->fails()) {
            return response()->json($validation->messages(), 422);
        }

        $itemsPerPage = 10;

        $orderQuery = Archive_order::query();

        if ($query != 'null') {
            
            $orderQuery->where('ref', 'LIKE', '%' . $query .'%');

        }

        if ($status != 'null') {
            
            $orderQuery->where('status', 'LIKE', '%' . $status .'%');

        }

        if ($start_date != 'null' && $end_date != 'null') {
            
            $orderQuery->whereBetween('created_at', [
                $start_date,
                Carbon::parse($end_date)->endOfDay()
            ]);

        }

        $orders = $orderQuery->orderBy('created_at', 'desc')
        ->get(['id', 'ref', 'quantity', 'received', 'status', 'created_at', 'total_price']);   
        
        return response()->json([
            'orders' => OrderResource::collection($orders->forPage($current_page, $itemsPerPage)),
            'total_pages' => ceil($orders->count()/$itemsPerPage)
        ]);

    }
    
}
