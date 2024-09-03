<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Crypt;

class NotificationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $order = $this->order->Archive()->first(['order_id', 'ref', 'user_id', 'received']);
        return [
            'id' => $this->id,
            'created_at' => $this->created_at->format('m-d'),
            'content' => $this->content,
            'subject' => $this->subject,
            'response' => $this->response,
            'email' => User::find($this->user_id)->email,
            'order' => isset($order) ? [
                'id' => Crypt::encrypt($order->order_id),
                'user_id' => $order->user_id,
                'received' => $order->received,
                'ref' => $order->ref
            ] : null,
            'user_id' => $this->user_id,
            'deleted_from_send' => $this->deleted_from_send,
            'deleted_from_receive' => $this->deleted_from_receive
        ];
    }
}
