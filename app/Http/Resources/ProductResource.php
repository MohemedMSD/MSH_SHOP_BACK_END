<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Category;
use App\Models\Review;
use App\Models\Product_view;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
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
            // 'images' => 'http://localhost:8000/uploads/earphones_b_2.webp',
            'discount' => isset($this->Discount) && $this->Discount->active ? [
                'active' => $this->Discount->active,
                'discount' => $this->Discount->discount,
            ] : null,
            'views' => $this->views()->sum('count'),
            'details' => Review::select(
                DB::raw('sum(review_star) / count(*) as moyen_reviews'),
                DB::raw('count(*) as reviews_count'),
            )
            ->where('product_id', $this->id)
            ->first()
        ];
    }

}
