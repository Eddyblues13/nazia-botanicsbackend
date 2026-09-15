<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // On update the record keeps its own slug; on create the slug is
        // derived from the name unless one is supplied.
        $productId = $this->route('product')?->id;

        return [
            'name' => ['required', 'string', 'max:150'],
            'slug' => [
                'nullable', 'string', 'max:150', 'alpha_dash',
                Rule::unique('products', 'slug')->ignore($productId),
            ],
            'tagline' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:5000'],

            // Every size the oil is sold in, each with its own price.
            'sizes' => ['required', 'array', 'min:1', 'max:10'],
            'sizes.*.label' => ['required', 'string', 'max:20'],
            'sizes.*.price' => ['required', 'integer', 'min:0', 'max:100000000'],
            // Optional: what the size costs to make. Drives profit reporting,
            // and stays null until the team has a figure they trust.
            'sizes.*.cost' => ['nullable', 'integer', 'min:0', 'max:100000000'],

            'highlights' => ['nullable', 'array', 'max:6'],
            'highlights.*.icon' => ['required', 'string', 'max:20'],
            'highlights.*.title' => ['required', 'string', 'max:60'],
            'highlights.*.detail' => ['required', 'string', 'max:120'],

            'ingredients' => ['nullable', 'array', 'max:12'],
            'ingredients.*.name' => ['required', 'string', 'max:60'],
            'ingredients.*.role' => ['required', 'string', 'max:60'],
            'ingredients.*.detail' => ['required', 'string', 'max:400'],

            'image' => ['nullable', 'string', 'max:2048', 'url'],
            'is_active' => ['boolean'],
            'position' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ];
    }

    public function messages(): array
    {
        return [
            'sizes.required' => 'A product needs at least one size.',
            'sizes.*.price.integer' => 'Prices are whole naira — no kobo, no commas.',
        ];
    }
}
