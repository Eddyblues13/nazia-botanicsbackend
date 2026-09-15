<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ArticleResource;
use App\Models\Article;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ArticleController extends Controller
{
    /**
     * Journal index cards. Bodies are withheld here and arrive on show, so the
     * index stays small however long the articles get.
     */
    public function index(): AnonymousResourceCollection
    {
        return ArticleResource::collection(Article::query()->published()->ordered()->get());
    }

    public function show(Article $article): ArticleResource
    {
        abort_unless($article->is_published, 404);

        return new ArticleResource($article);
    }
}
