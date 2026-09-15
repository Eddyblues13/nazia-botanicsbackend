<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WaitlistSignupResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'phone' => $this->phone,
            'invited_at' => $this->invited_at?->toIso8601String(),
            'joined_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
