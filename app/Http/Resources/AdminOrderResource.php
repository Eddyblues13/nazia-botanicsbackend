<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'status' => $this->status,
            'customer_name' => $this->customer_name,
            'customer_phone' => $this->customer_phone,
            'customer_email' => $this->customer_email,
            'delivery_address' => $this->delivery_address,
            'note' => $this->note,
            'subtotal' => $this->subtotal,
            'placed_at' => $this->created_at?->toIso8601String(),
            // Present on list rows via withCount, so the table can show a
            // line count without loading every item.
            'items_count' => $this->when(
                $this->items_count !== null,
                fn () => (int) $this->items_count
            ),
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($item) => [
                'id' => $item->id,
                'product_slug' => $item->product_slug,
                'name' => $item->product_name,
                'size' => $item->size,
                'qty' => $item->qty,
                'unit_price' => $item->unit_price,
                'line_total' => $item->line_total,
            ])),
        ];
    }
}
