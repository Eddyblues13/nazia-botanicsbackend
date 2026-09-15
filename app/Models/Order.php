<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Order extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_SHIPPED = 'shipped';

    public const STATUS_DELIVERED = 'delivered';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_CONFIRMED,
        self::STATUS_SHIPPED,
        self::STATUS_DELIVERED,
        self::STATUS_CANCELLED,
    ];

    /**
     * Cancelled orders are excluded from every revenue figure.
     */
    public const REVENUE_STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_CONFIRMED,
        self::STATUS_SHIPPED,
        self::STATUS_DELIVERED,
    ];

    protected $fillable = [
        'reference',
        'customer_name',
        'customer_phone',
        'customer_email',
        'delivery_address',
        'note',
        'subtotal',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'integer',
        ];
    }

    /**
     * Cost across the lines that have one. Null when no line does, which keeps
     * "never costed" separate from "cost nothing".
     */
    public function costTotal(): ?int
    {
        $costed = $this->items->whereNotNull('line_cost');

        return $costed->isEmpty() ? null : (int) $costed->sum('line_cost');
    }

    /**
     * Margin over the costed lines only — an order that is half costed reports
     * the profit of that half rather than pretending about the rest.
     */
    public function profitTotal(): ?int
    {
        $costed = $this->items->whereNotNull('line_cost');

        return $costed->isEmpty()
            ? null
            : (int) ($costed->sum('line_total') - $costed->sum('line_cost'));
    }

    public function getRouteKeyName(): string
    {
        return 'reference';
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Human-quotable reference the customer can read out over the phone.
     */
    public static function generateReference(): string
    {
        do {
            $reference = 'NB-'.now()->format('ymd').'-'.Str::upper(Str::random(4));
        } while (static::where('reference', $reference)->exists());

        return $reference;
    }
}
