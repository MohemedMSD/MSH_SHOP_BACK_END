<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use App\Models\User;
use App\Models\Order;
use App\Models\Archive_order;
use Illuminate\Support\Facades\Validator;   
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Broadcast;
use App\Models\OrderStatus;
use App\Events\SendNewNotification;

class NotificationController extends Controller
{
    //
    public function __construct(){

        $this->middleware('role:seller')->only([
            'resend'
        ]);

    }

    // get all notification
    public function index(Request $request){
        
        if($request->user()->role->name == 'costumer'){

            $notifications = Notification::where('user_id', $request->user()->id)
            ->where('deleted_from_receive' , 0)
            ->orderBy('created_at', 'desc')
            ->get();

        } else if($request->user()->role->name == 'seller'){

            $notifications = Notification::where('deleted_from_receive' , 0)
            ->where('user_id', $request->user()->id)
            ->orWhere('deleted_from_send' , 0)
            ->orderBy('created_at', 'desc')
            ->get();

        }


        return NotificationResource::collection($notifications);

    }

    /**
     * method for confirmed or not the received of order.
     */
    public function confirmatedOrNot(Request $request, $id)
    {
        //
        $validation = Validator::make($request->all(), [
            'confirmated' => 'required|boolean'
        ]);

        if ($validation->fails()) {
            
            return response()->json($validation->messages(), 203);

        }

        $notification = Notification::find($id);
        $order = $notification->order;
        $archive_order = Archive_order::where('session_id', $order->session_id)
        ->first();

        if ($request->confirmated) {
            
            $notification->update([
                'response' => 'confirmated',
                'created_at' => now()
            ]);

            $order->update([
                'received' => 1
            ]);

            $archive_order->update([
                'received' => 1
            ]);

        }else{

            $notification->update([
                'response' => 'Not confirmated',
                'created_at' => now()
            ]);

        }

        event(new SendNewNotification([
            'id' => $notification->id,
            'created_at' => $notification->created_at->format('m-d'),
            'content' => $notification->content,
            'subject' => $notification->subject,
            'response' => $notification->response,
            'email' => User::find($notification->user_id)->email,
            'user_id' => $notification->user_id,
            'deleted_from_send' => $notification->deleted_from_send,
            'deleted_from_receive' => $notification->deleted_from_receive,
            'order' => [
                'id' => Crypt::encrypt($archive_order->order_id),
                'user_id' => $archive_order->user_id,
                'received' => $archive_order->received,
                'ref' => $archive_order->ref
            ],
        ]));

        return response()->json([
            'notification' => new NotificationResource($notification),
            'order' => [
                'id' => Crypt::encrypt($archive_order->id),
                'ref' => $order->ref,
                'quantity' => $order->quantity,
                'price' => $order->total_price,
                'status' => OrderStatus::find($order->order_status_id)->statut,
                'created_at' => $order->created_at->format('Y-m-d'),
                'received' => $order->received,
                'part' => OrderStatus::find($order->order_status_id)->part
            ]
        ]);

    }

    // method for resend order confirmation 
    public function resend($id){

        $notification = Notification::find($id);
        
        if (!$notification->order->received) {
            
            $notification->update([
                'response' => 'pending',
                'deleted_from_receive' => 0,
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
            return new NotificationResource($notification);
            // return response()->json(200);

        }

    }

    public function delete(Request $request, $id, $delete_from){

        $notification = Notification::find($id);
        $user = $request->user();
        $deleted = false;
        $action = false;
        
        switch ($delete_from) {

            case 'receive' && $notification->deleted_from_send || 'send' && $notification->deleted_from_receive:
                $deleted = true;
                $action = true;
                $notification->delete();
                break;

            case 'receive':
                $action = true;
                $notification->update([
                    'deleted_from_receive' => 1
                ]);
                break;

            case 'send':
                $action = true;
                $notification->update([
                    'deleted_from_send' => 1
                ]);
                break;
        }

        if ($action) {

            if ($deleted) {
                return response()->json('notification deleted successfully');
            }else{

                return new NotificationResource($notification);
                
            }

        }

    }

}
