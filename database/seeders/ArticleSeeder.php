<?php

namespace Database\Seeders;

use App\Models\Article;
use Illuminate\Database\Seeder;

class ArticleSeeder extends Seeder
{
    /**
     * The journal as it was written, kept in a JSON fixture beside this file
     * so the prose stays readable and diffable rather than buried in a PHP
     * array literal.
     */
    public function run(): void
    {
        $articles = json_decode(
            file_get_contents(__DIR__.'/data/articles.json'),
            associative: true,
            flags: JSON_THROW_ON_ERROR,
        );

        foreach ($articles as $article) {
            Article::updateOrCreate(
                ['slug' => $article['slug']],
                [
                    ...$article,
                    'is_published' => true,
                ],
            );
        }
    }
}
