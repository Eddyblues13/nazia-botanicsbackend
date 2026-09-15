<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NewsletterSubscriberResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'source' => $this->source,
            'unsubscribed_at' => $this->unsubscribed_at?->toIso8601String(),
            'subscribed_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
