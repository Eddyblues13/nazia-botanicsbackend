<?php

namespace Database\Seeders;

use App\Models\Review;
use Illuminate\Database\Seeder;

class ReviewSeeder extends Seeder
{
    /**
     * Seeded reviews are approved on the way in — they are the ones the
     * homepage already shipped with. Anything arriving from the storefront
     * form starts unapproved instead.
     */
    public function run(): void
    {
        $reviews = json_decode(
            file_get_contents(__DIR__.'/data/reviews.json'),
            associative: true,
            flags: JSON_THROW_ON_ERROR,
        );

        foreach ($reviews as $review) {
            Review::firstOrCreate(
                ['name' => $review['name'], 'text' => $review['text']],
                [
                    'location' => $review['location'],
                    'rating' => $review['rating'],
                    'position' => $review['position'],
                    'is_approved' => true,
                ],
            );
        }
    }
}
