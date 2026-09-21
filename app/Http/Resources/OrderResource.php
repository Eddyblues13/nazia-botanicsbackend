<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'reference' => $this->reference,
            'status' => $this->status,
            'customer_name' => $this->customer_name,
            'customer_phone' => $this->customer_phone,
            'customer_email' => $this->customer_email,
            'delivery_address' => $this->delivery_address,
            'note' => $this->note,
            'subtotal' => $this->subtotal,
            'delivery_zone' => $this->delivery_zone,
            'delivery_fee' => $this->delivery_fee,
            'delivery_period' => $this->delivery_period,
            'total' => $this->total,
            'payment_status' => $this->payment_status,
            // Whether this order already sits on an account, so the storefront
            // knows not to offer to save it again. Deliberately a boolean and
            // not the account's id — which account is nobody else's business.
            'in_account' => $this->user_id !== null,
            'paid_at' => $this->paid_at?->toIso8601String(),
            'placed_at' => $this->created_at?->toIso8601String(),
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($item) => [
                'product_id' => $item->product_slug,
                'name' => $item->product_name,
                'size' => $item->size,
                'qty' => $item->qty,
                'unit_price' => $item->unit_price,
                'line_total' => $item->line_total,
            ])),
        ];
    }
}
