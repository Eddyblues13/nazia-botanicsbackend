<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'tagline',
        'price_from',
        'description',
        'sizes',
        'highlights',
        'ingredients',
        'image',
        'is_active',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'price_from' => 'integer',
            'sizes' => 'array',
            'highlights' => 'array',
            'ingredients' => 'array',
            'is_active' => 'boolean',
            'position' => 'integer',
        ];
    }

    /**
     * The storefront addresses products by slug, not by numeric id.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * The price of one size, or null when the label is not one this product
     * is sold in. Orders price their lines through here so a tampered cart
     * cannot invent a size.
     */
    public function priceForSize(string $label): ?int
    {
        foreach ($this->sizes as $size) {
            if (($size['label'] ?? null) === $label) {
                return (int) $size['price'];
            }
        }

        return null;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('position')->orderBy('id');
    }
}
