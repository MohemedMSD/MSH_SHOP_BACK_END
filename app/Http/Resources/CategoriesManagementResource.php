<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoriesManagementResource extends JsonResource
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
            'image' => isset($this->image) ? app('firebase.storage')->getBucket()->object($this->image)
            ->signedUrl(new \DateTime('1 hour')) : '',
            'numbre_products' => $this->products->count()
        ];
    }
}
