<?php

namespace Database\Seeders;

use App\Models\DeliveryZone;
use Illuminate\Database\Seeder;

class DeliveryZoneSeeder extends Seeder
{
    /**
     * Lagos, grouped the way deliveries are actually priced.
     *
     * A state is too coarse to charge by — Lekki and Ikorodu are both Lagos
     * and are not the same trip — so each zone is a set of areas that cost the
     * same to reach. The areas are real places; the prices are placeholders
     * for the shop to set under Delivery in the dashboard.
     *
     * Nothing starts active. An active zone is a price the checkout will
     * charge, and nobody has chosen these yet.
     */
    public function run(): void
    {
        $zones = [
            ['Mainland 1', 3500, ['Ogudu', 'Gbagada', 'Surulere', 'Ojota', 'Magodo', 'Maryland', 'Ikeja', 'Ketu', 'Yaba'], 10],
            ['Mainland 2', 4000, ['Iyana Ipaja', 'Egbeda', 'Agege', 'Ago Palace', 'Mile 2', 'Festac', 'Satellite Town', 'Ijegun', 'Iju Ishaga', 'Abule Egba'], 20],
            ['Mainland 3', 5000, ['Ikorodu', 'Ikotun', 'Ipaja', 'Idimu', 'Ijegun'], 30],
            ['Mainland 4', 4000, ['Apapa', 'Ajegunle', 'Ijora', 'Costain', 'Alaba', 'Suru'], 40],
            ['Island 1', 4500, ['Lekki Phase 1', 'Victoria Island', 'Ikoyi', 'Banana Island', 'Ikate'], 50],
            ['Island 2', 5000, ['VGC', 'Agungi', 'Osapa London', 'Chevron'], 60],
            ['Island 3', 5500, ['Ajah', 'Sangotedo', 'Abijo'], 70],
        ];

        foreach ($zones as [$name, $fee, $areas, $order]) {
            DeliveryZone::firstOrCreate(
                ['name' => $name],
                [
                    'areas' => $areas,
                    'fee' => $fee,
                    'delivery_period' => '1-2 working days after dispatch',
                    'is_active' => false,
                    'sort_order' => $order,
                ],
            );
        }
    }
}
