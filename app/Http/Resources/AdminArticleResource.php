<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminArticleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'tag' => $this->tag,
            'title' => $this->title,
            'excerpt' => $this->excerpt,
            'minutes' => $this->minutes,
            'tone' => $this->tone,
            'cta' => $this->cta,
            'body' => $this->body,
            'next_up' => $this->next_up,
            'image' => $this->image,
            'is_published' => $this->is_published,
            'position' => $this->position,
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
