<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    public const TONES = ['sage', 'terracotta', 'clay'];

    protected $fillable = [
        'slug',
        'tag',
        'title',
        'excerpt',
        'minutes',
        'tone',
        'cta',
        'body',
        'next_up',
        'image',
        'is_published',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'minutes' => 'integer',
            'body' => 'array',
            'next_up' => 'array',
            'is_published' => 'boolean',
            'position' => 'integer',
        ];
    }

    /**
     * The journal addresses articles by slug — /journal/scalp-massage-ritual.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('position')->orderBy('id');
    }
}
