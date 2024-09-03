<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DiscountProductsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'price' => $this->price,
            'quantity' => $this->quantity,
            'category_name' => $this->category()->first('name')->name,
            'images' => app('firebase.storage')->getBucket()->object($this->images[0])
            ->signedUrl(new \DateTime('1 hour')),
            'discount' => isset($this->Discount) && $this->Discount->active ? [
                'active' => $this->Discount->active,
                'discount' => $this->Discount->discount,
                'color' => $this->Discount->color,
                'start_date' => $this->Discount->start_date,
                'end_date' => $this->Discount->end_date,
                'title' => $this->Discount->title,
                'desc' => $this->Discount->desc,
            ] : null
        ];
    }
}
