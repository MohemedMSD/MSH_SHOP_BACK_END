<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use App\Models\Category;
use Illuminate\Http\Resources\Json\JsonResource;

class TrashedProductResource extends JsonResource
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
            'category_name' => Category::find($this->category_id)->name
        ];;
    }
}
