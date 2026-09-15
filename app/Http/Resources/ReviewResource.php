<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'location' => $this->location,
            'rating' => $this->rating,
            'text' => $this->text,
            // Only the dashboard asks for unapproved rows, so this is the one
            // field the storefront has no use for.
            'is_approved' => $this->when($request->is('api/admin/*'), fn () => $this->is_approved),
            'submitted_at' => $this->when(
                $request->is('api/admin/*'),
                fn () => $this->created_at?->toIso8601String()
            ),
        ];
    }
}
