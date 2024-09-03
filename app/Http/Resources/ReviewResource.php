<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Crypt;

class ReviewResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // $product = $this->product;
        $user = $this->user;
        $defaultBucket = app('firebase.storage')->getBucket();
        $imagesUrl = [];

        if (isset($this->images) && count($this->images) > 0) {
            
            foreach($this->images as $img){
                $imagesUrl[] = $defaultBucket->object($img)
                ->signedUrl(new \DateTime('15 minutes'));
            }

        }

        return [
            'id' => Crypt::encrypt($this->id),
            'review_star' => $this->review_star,
            'review' => $this->review,
            'ReviewPermission' => Auth('api')->check() ? $this->user->id == Auth('api')->user()->id : false,
            // 'images' => [
            //     'http://localhost:8000/uploads/3_todo-list-app.jpg',
            //     'http://localhost:8000/uploads/705_hoobank.jpg',
            //     'http://localhost:8000/uploads/970_headphones_b_2.webp',
            //     'http://localhost:8000/uploads/3youtube-clone.jpg',
            // ],
            'images' => $imagesUrl,
            'images_path' => $this->images,
            'updated_at' => $this->updated_at->format('Y-m-d'),
            // 'product'=> [
            //     'title' => $product->title,
            //     'price' => $product->price,
            //     'image' => app('firebase.storage')->getBucket()
            //     ->object($product->images[0])->signedUrl(new \DateTime('5 min'))
            // ],
            'user' => [
                'id' => Crypt::encrypt($user->user),
                'name' => $user->name,
                'profile' => app('firebase.storage')->getBucket()
                ->object($user->profile)->signedUrl(new \DateTime('5 min'))
            ]
        ];
    }
}
