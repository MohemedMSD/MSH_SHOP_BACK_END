<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Crypt;

class CardResource extends JsonResource
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
            'quantity' => $this->quantity,
            'product' => [
                'id' => $this->product->id,
                'name' => $this->product->name,
                'price' => $this->product->price,
                'quantity' => $this->product->quantity,
                'images' => app('firebase.storage')->getBucket()
                ->object($this->product->images[0])
                ->signedUrl(new \DateTime('1 hour')),
                'discount' => [
                    'discount' => $this->product->Discount ? $this->product->Discount->discount : null
                ]
            ]
        ];
    }
}
