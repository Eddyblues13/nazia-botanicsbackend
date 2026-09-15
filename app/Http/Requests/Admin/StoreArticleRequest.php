<?php

namespace App\Http\Requests\Admin;

use App\Models\Article;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreArticleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $articleId = $this->route('article')?->id;

        return [
            'title' => ['required', 'string', 'max:200'],
            'slug' => [
                'nullable', 'string', 'max:200', 'alpha_dash',
                Rule::unique('articles', 'slug')->ignore($articleId),
            ],
            'tag' => ['required', 'string', 'max:40'],
            'excerpt' => ['required', 'string', 'max:400'],
            'minutes' => ['nullable', 'integer', 'min:1', 'max:120'],
            'tone' => ['nullable', Rule::in(Article::TONES)],
            'cta' => ['nullable', 'string', 'max:120'],

            // Ordered blocks. `text` belongs to p/h, `items` to ul/ol — the
            // controller enforces which one each type needs.
            'body' => ['required', 'array', 'min:1', 'max:200'],
            'body.*.type' => ['required', Rule::in(['p', 'h', 'ul', 'ol'])],
            'body.*.text' => ['nullable', 'string', 'max:5000'],
            'body.*.items' => ['nullable', 'array', 'max:50'],
            'body.*.items.*' => ['required', 'string', 'max:2000'],

            'next_up' => ['nullable', 'array'],
            'next_up.id' => ['required_with:next_up', 'string', 'max:200', 'exists:articles,slug'],
            'next_up.teaser' => ['required_with:next_up', 'string', 'max:1000'],

            'image' => ['nullable', 'string', 'max:2048', 'url'],
            'is_published' => ['boolean'],
            'position' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ];
    }

    public function messages(): array
    {
        return [
            'next_up.id.exists' => 'The "read next" article has to be one that already exists.',
        ];
    }
}
