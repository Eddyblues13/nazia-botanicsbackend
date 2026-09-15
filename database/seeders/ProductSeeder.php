<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * The catalog as the storefront shipped it, so a fresh database renders
     * the same shop the static build did.
     */
    public function run(): void
    {
        Product::updateOrCreate(
            ['slug' => 'botanical-growth-oil'],
            [
                'name' => 'Botanical Growth Oil',
                'tagline' => 'Scalp and Hair Oil',
                'price_from' => 15000,
                'description' => 'Small-batch, cold-infused botanicals — formulated to treat shedding at its source, not the surface. Rosemary to stimulate, ashwagandha to calm, hibiscus to strengthen and bhringraj to nourish.',
                'sizes' => [
                    ['label' => '2 oz', 'price' => 15000],
                    ['label' => '4 oz', 'price' => 25000],
                ],
                'highlights' => [
                    ['icon' => 'sprout', 'title' => 'Stimulates Growth', 'detail' => 'Rosemary'],
                    ['icon' => 'strand', 'title' => 'Strengthens Strands', 'detail' => 'Hibiscus'],
                    ['icon' => 'calm', 'title' => 'Reduces Stress-Shedding', 'detail' => 'Ashwagandha'],
                ],
                'ingredients' => [
                    [
                        'name' => 'Rosemary',
                        'role' => 'The Stimulator',
                        'detail' => 'Clinically shown to match 2% minoxidil at increasing hair count — by waking dormant follicles with fresh, oxygenated blood flow.',
                    ],
                    [
                        'name' => 'Ashwagandha',
                        'role' => 'The Adaptogen',
                        'detail' => 'The king of adaptogens calms the scalp’s cortisol response, keeping follicles in their growth phase instead of survival mode.',
                    ],
                    [
                        'name' => 'Hibiscus',
                        'role' => 'The Strengthener',
                        'detail' => 'Rich in amino acids that reinforce each strand from within, reducing breakage and adding a natural, healthy lustre.',
                    ],
                    [
                        'name' => 'Bhringraj',
                        'role' => 'The King of Hair',
                        'detail' => 'Prized in Ayurveda for promoting growth, reducing hair fall and premature graying while deeply nourishing the follicle.',
                    ],
                ],
                'is_active' => true,
                'position' => 1,
            ],
        );
    }
}
