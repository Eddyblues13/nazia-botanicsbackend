<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Matches the journal's existing article shape, so `id` is the slug and the
 * hand-off block keeps the name the pages already read — `next`.
 */
class ArticleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->slug,
            'tag' => $this->tag,
            'title' => $this->title,
            'excerpt' => $this->excerpt,
            'minutes' => $this->minutes,
            'tone' => $this->tone,
            'cta' => $this->cta,
            'image' => $this->image,
            // The index lists cards only; bodies ride along on the show route.
            'body' => $this->when(! $request->routeIs('storefront.articles.index'), fn () => $this->body),
            'next' => $this->next_up,
        ];
    }
}
