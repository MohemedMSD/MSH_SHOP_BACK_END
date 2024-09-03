<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\OrderDetails;
use App\Models\OrderStatus;
use App\Models\User;
use App\Models\Archive_order;
use App\Models\Notification;
use App\Http\Resources\UserOrderResource;
use App\Http\Resources\NotificationResource;
use App\Http\Resources\StatusResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Validator;
use App\Events\SendNewNotification;

class OrderController extends Controller
{
    public function __construct(){
        $this->middleware('role:seller')->only(['index', 'update', 'status']);
    }
    
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        $orders = Archive_order::orderBy('created_at', 'desc')
        ->get();
        
        return response()->json($orders);

    }


    public function getUserOrders(Request $request)
    {

        $orders = $request->user()->archiveOrders()
        // ->where('received', 0)
        ->orderBy('created_at', 'desc')
        ->get(['id', 'ref', 'created_at', 'products', 'quantity', 'adress', 'received', 'status', 'total_price']);
        

        return UserOrderResource::collection($orders);

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
        // $validation = Validator::make($request->all(), [
        //     'products' => [
        //         'required', 
        //         function ($attribute, $value, $fail) use ($request){

        //             foreach($request->products as $productBuy){

        //                 $product = Product::find($productBuy['id']);

        //                 if ($product->quantity < $productBuy['quantity']) {
        //                     $fail('we have just ' . $product->quantity . ' from ' . $product->name . ' in our stock and you want ' . $productBuy['quantity']);
        //                 }

        //             }

        //         } 
        //     ],
        // ]);

        // if ($validation->fails()) {
            
        //     return response()->json($validation->messages(), 422);

        // }
        
        $user = Auth()->user();
        $total_quantity = 0;
        $total_price = 0;

        $OrderCreated = false;

        foreach($request->products as $productBuy){
            
            $product = Product::find($productBuy['id']);

            if (isset($product)) {

                if (!$OrderCreated) {

                    $order = Order::create([
                        'user_id' => $user->id,
                        'quantity' => 0,
                        'total_price' =>0,
                        'adress' => $request->adress,
                        'session_id' => $request->session_id,
                        'order_status_id' => OrderStatus::where('part', 1)->first()->id
                    ]);

                    $OrderCreated = true;

                }

                OrderDetails::create([
                    'order_id' => $order->id,
                    'product_id' => $productBuy['id'],
                    'price' => $productBuy['price_per_product'] * $productBuy['quantity'],
                    'quantity' => $productBuy['quantity'],
                ]);

                $total_quantity += $productBuy['quantity'];
                $total_price += $productBuy['price_per_product'] * $productBuy['quantity'];

                // $product->update([
                //     'quantity' => $product->quantity - $productBuy['quantity']
                // ]);

            }

        }

        $order->update([
            'user_id' => $user->id,
            'quantity' => $total_quantity,
            'total_price' => $total_price,
        ]);

        $notification = Notification::create([
            'subject' => 'Payemenet cancellation',
            'response' => '',
            'content' => 'Your payement cancelled',
            'user_id' => $order->user_id,
            'order_id' => $order->id,
            'deleted_from_send' => 1
        ]);

        event(new SendNewNotification([
            'id' => $notification->id,
            'created_at' => $notification->created_at->format('m-d'),
            'content' => $notification->content,
            'subject' => $notification->subject,
            'response' => $notification->response,
            'email' => User::find($notification->user_id)->email,
            'order' => [],
            'user_id' => $notification->user_id,
            'deleted_from_send' => $notification->deleted_from_send,
            'deleted_from_receive' => $notification->deleted_from_receive
        ]));
        
        return response()->json([
            'id' => Crypt::encrypt($order->id),
            'quantity' => $order->quantity,
            'price' => $order->total_price,
            'status' => OrderStatus::find($order->order_status_id)->statut,
            'date' => $order->created_at->format('Y-m-d'),
            'received' => $order->received,
            'part' => OrderStatus::find($order->order_status_id)->part
        ]);

    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        //
        try {

            $id = Crypt::decrypt($id);

            $order = Archive_order::where('order_id', $id)->first();

            return response()->json($order);

        } catch (\Throwable $e) {
            
            return response()->json('Something not correct', 422);

        }

    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        //
        $validation = Validator::make($request->all(), [
            'status' => 'required|exists:order_status,id'
        ]);

        if ($validation->fails()) {
            
            return response()->json($validation->messages(), 422);

        }

        try {

            $id = Crypt::decrypt($id);
            
            $order = Order::where('id', $id)->first();
            
            $archive_order = Archive_order::where('session_id', $order->session_id)->first();
            $status = OrderStatus::find($request->status);

            if (isset($status)) {

                $order->update([
                    'order_status_id' => $status->id
                ]);

                $archive_order->update([
                    'status' => $status->statut,
                    'part' => $status->part
                ]);

            }
            
            $notification = Notification::where('order_id', $order->id)->first();

            if ($order->order_status_id == 4) {

                if (isset($notification)) {
                    
                    if (
                        $notification->response != 'pending' ||
                        $notification->deleted_from_send == 1 || 
                        $notification->deleted_from_send == 1
                    ) {
                        
                        $notification->update([
                            'response' => 'pending',
                            'deleted_from_send' => 0,
                            'deleted_from_receive' => 0
                        ]);

                    }

                } else{

                    $notification = Notification::create([
                        'subject' => 'Received Confirmation For Order Num ' . $archive_order->ref,
                        'response' => 'pending',
                        'content' => 'Do you Received your Order Num: ' . $archive_order->ref .  ' ?',
                        'user_id' => $order->user_id,
                        'order_id' => $order->id
                    ]);

                }

                $Order_notification = $notification->order->Archive()->first(['order_id', 'ref', 'user_id', 'received']);

                event(new SendNewNotification([
                    'id' => $notification->id,
                    'created_at' => $notification->created_at->format('m-d'),
                    'content' => $notification->content,
                    'subject' => $notification->subject,
                    'response' => $notification->response,
                    'email' => User::find($notification->user_id)->email,
                    'order' => [
                        'id' => Crypt::encrypt($Order_notification->order_id),
                        'user_id' => $Order_notification->user_id,
                        'received' => $Order_notification->received,
                        'ref' => $Order_notification->ref
                    ],
                    'user_id' => $notification->user_id,
                    'deleted_from_send' => $notification->deleted_from_send,
                    'deleted_from_receive' => $notification->deleted_from_receive
                ]));

                return response()->json([
                    'order' => [
                        'status' => $archive_order->status
                    ],
                    // 'notification' => new NotificationResource($notification)
                ]);

            }else{

                if (isset($notification)) {

                    $notification->update([
                        'deleted_from_send' => 1,
                        'deleted_from_receive' => 1
                    ]);

                    $Order_notification = $notification->order->Archive()->first(['order_id', 'ref', 'user_id', 'received']);

                    event(new SendNewNotification([
                        'id' => $notification->id,
                        'created_at' => $notification->created_at->format('m-d'),
                        'content' => $notification->content,
                        'subject' => $notification->subject,
                        'response' => $notification->response,
                        'email' => User::find($notification->user_id)->email,
                        'order' => [
                            'id' => Crypt::encrypt($Order_notification->order_id),
                            'user_id' => $Order_notification->user_id,
                            'received' => $Order_notification->received,
                            'ref' => $Order_notification->ref
                        ],
                        'user_id' => $notification->user_id,
                        'deleted_from_send' => $notification->deleted_from_send,
                        'deleted_from_receive' => $notification->deleted_from_receive
                    ]));

                    return response()->json([
                        'order' => [
                            'status' => $archive_order->status
                        ],
                        // 'notification' => new NotificationResource($notification)
                    ]);

                }

            }

            return response()->json([
                'order' => [
                    'status' => $archive_order->status
                ],
                'notification' => []
            ]);

        } catch (\Throwable $e) {
            
            return response()->json('Something not correct', 422);

        }

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Order $order)
    {
        //
    }

    public function status(){
        $status = OrderStatus::orderBy('part')->get();
        return StatusResource::collection($status);
    }
}
