<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreArticleRequest;
use App\Http\Resources\AdminArticleResource;
use App\Models\Article;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ArticleController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'in:published,draft'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:100'],
        ]);

        $articles = Article::query()
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where(
                fn ($q) => $q->where('title', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('tag', 'like', "%{$search}%")
            ))
            ->when(isset($filters['status']), fn ($query) => $query->where(
                'is_published',
                $filters['status'] === 'published'
            ))
            ->ordered()
            ->paginate($filters['per_page'] ?? 20)
            ->withQueryString();

        return AdminArticleResource::collection($articles);
    }

    public function store(StoreArticleRequest $request): JsonResponse
    {
        $article = Article::create($this->payload($request));

        return (new AdminArticleResource($article))->response()->setStatusCode(201);
    }

    public function show(Article $article): AdminArticleResource
    {
        return new AdminArticleResource($article);
    }

    public function update(StoreArticleRequest $request, Article $article): AdminArticleResource
    {
        $article->update($this->payload($request, $article));

        return new AdminArticleResource($article->fresh());
    }

    /**
     * Deleting an article leaves anything that pointed at it as "read next"
     * dangling, so those hand-offs are cleared in the same breath.
     */
    public function destroy(Article $article): JsonResponse
    {
        Article::query()
            ->where('next_up->id', $article->slug)
            ->update(['next_up' => null]);

        $article->delete();

        return response()->json(['message' => 'Article deleted.']);
    }

    public function toggle(Article $article): AdminArticleResource
    {
        $article->update(['is_published' => ! $article->is_published]);

        return new AdminArticleResource($article);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(StoreArticleRequest $request, ?Article $article = null): array
    {
        $data = $request->validated();

        $data['slug'] = $data['slug']
            ?? $article?->slug
            ?? $this->uniqueSlug($data['title']);

        if (($data['next_up']['id'] ?? null) === $data['slug']) {
            throw ValidationException::withMessages([
                'next_up.id' => 'An article cannot point at itself as the next read.',
            ]);
        }

        $data['body'] = $this->normalizeBody($data['body']);
        $data['minutes'] = $data['minutes'] ?? $this->estimateMinutes($data['body']);
        $data['tone'] = $data['tone'] ?? 'sage';
        $data['is_published'] = $data['is_published'] ?? true;
        $data['position'] = $data['position'] ?? (Article::max('position') + 1);

        return $data;
    }

    /**
     * Keeps each block to the one payload its type renders: paragraphs and
     * headings carry `text`, lists carry `items`.
     *
     * @param  list<array<string, mixed>>  $body
     * @return list<array<string, mixed>>
     */
    private function normalizeBody(array $body): array
    {
        return collect($body)->values()->map(function (array $block, int $index) {
            $isList = in_array($block['type'], ['ul', 'ol'], true);

            if ($isList) {
                $items = array_values(array_filter(
                    $block['items'] ?? [],
                    fn ($item) => filled($item)
                ));

                if ($items === []) {
                    throw ValidationException::withMessages([
                        "body.{$index}.items" => 'A list block needs at least one item.',
                    ]);
                }

                return ['type' => $block['type'], 'items' => $items];
            }

            if (blank($block['text'] ?? null)) {
                throw ValidationException::withMessages([
                    "body.{$index}.text" => 'This block needs some text.',
                ]);
            }

            return ['type' => $block['type'], 'text' => $block['text']];
        })->all();
    }

    /**
     * Rounded up from a 200-words-per-minute read of everything in the body.
     *
     * @param  list<array<string, mixed>>  $body
     */
    private function estimateMinutes(array $body): int
    {
        $words = collect($body)->sum(fn (array $block) => str_word_count(
            $block['text'] ?? implode(' ', $block['items'] ?? [])
        ));

        return max(1, (int) ceil($words / 200));
    }

    private function uniqueSlug(string $title): string
    {
        $base = Str::slug($title);
        $slug = $base;
        $suffix = 2;

        while (Article::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
