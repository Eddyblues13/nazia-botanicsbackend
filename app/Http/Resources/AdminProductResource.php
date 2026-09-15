<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The dashboard needs the fields the storefront never sees — the numeric id it
 * edits by, the active flag, and how many units have sold.
 */
class AdminProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'tagline' => $this->tagline,
            'price_from' => $this->price_from,
            'description' => $this->description,
            'sizes' => $this->sizes,
            'highlights' => $this->highlights,
            'ingredients' => $this->ingredients,
            'image' => $this->image,
            'is_active' => $this->is_active,
            'position' => $this->position,
            'units_sold' => (int) ($this->order_items_sum_qty ?? 0),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
