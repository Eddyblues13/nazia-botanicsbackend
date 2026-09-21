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
            'delivery_state' => $this->delivery_state,
            'delivery_fee' => $this->delivery_fee,
            'delivery_period' => $this->delivery_period,
            'total' => $this->total,
            'payment_status' => $this->payment_status,
            'payment_channel' => $this->payment_channel,
            'payment_reference' => $this->payment_reference,
            'amount_paid' => $this->amount_paid,
            'paid_at' => $this->paid_at?->toIso8601String(),
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
                // Null where no cost was recorded at the time of sale.
                'unit_cost' => $item->unit_cost,
                'line_cost' => $item->line_cost,
                'profit' => $item->profit(),
            ])),

            // Order-level margin, over the lines that carry a cost. Null when
            // none of them do, so the dashboard can say "not tracked" instead
            // of showing a profit equal to the full subtotal.
            'cost_total' => $this->whenLoaded('items', fn () => $this->costTotal()),
            'profit_total' => $this->whenLoaded('items', fn () => $this->profitTotal()),
            'fully_costed' => $this->whenLoaded(
                'items',
                fn () => $this->items->every(fn ($i) => $i->line_cost !== null)
            ),
        ];
    }
}
