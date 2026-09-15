<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $fillable = [
        'product_id',
        'product_name',
        'product_slug',
        'size',
        'qty',
        'unit_price',
        'line_total',
        'unit_cost',
        'line_cost',
    ];

    protected function casts(): array
    {
        return [
            'qty' => 'integer',
            'unit_price' => 'integer',
            'line_total' => 'integer',
            'unit_cost' => 'integer',
            'line_cost' => 'integer',
        ];
    }

    /**
     * Margin on this line, or null when the cost was never recorded — the
     * caller has to decide what to do about an unknown rather than being
     * handed a zero that looks like fact.
     */
    public function profit(): ?int
    {
        return $this->line_cost === null ? null : $this->line_total - $this->line_cost;
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
