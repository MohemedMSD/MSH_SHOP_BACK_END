<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => Crypt::encrypt($this->id),
            'ref' => $this->ref,
            'total_price' => $this->total_price,
            'quantity' => $this->quantity ,
            'received' => $this->received ,
            'status' => $this->status ,
            'created_at' => $this->created_at ,
        ];
    }
}
