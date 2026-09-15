<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Mirrors the shape the storefront already renders, so `id` is the slug and
 * `sizes` arrives ready for the spotlight's size picker.
 */
class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->slug,
            'name' => $this->name,
            'tagline' => $this->tagline,
            'priceFrom' => $this->price_from,
            'description' => $this->description,
            'sizes' => $this->sizes,
            'highlights' => $this->highlights,
            'ingredients' => $this->ingredients,
            'image' => $this->image,
        ];
    }
}
