<?php

namespace Database\Seeders;

use App\Models\DeliveryZone;
use Illuminate\Database\Seeder;

class DeliveryZoneSeeder extends Seeder
{
    /**
     * Every state, so the checkout dropdown is complete from day one.
     *
     * The fees and periods here are placeholders — the shop edits them under
     * Delivery in the dashboard. Seeding them all as active would quietly
     * promise a price nobody chose, so only Lagos starts active and the rest
     * wait to be priced.
     */
    public function run(): void
    {
        $states = [
            'Abia', 'Adamawa', 'Akwa Ibom', 'Anambra', 'Bauchi', 'Bayelsa',
            'Benue', 'Borno', 'Cross River', 'Delta', 'Ebonyi', 'Edo', 'Ekiti',
            'Enugu', 'FCT - Abuja', 'Gombe', 'Imo', 'Jigawa', 'Kaduna', 'Kano',
            'Katsina', 'Kebbi', 'Kogi', 'Kwara', 'Lagos', 'Nasarawa', 'Niger',
            'Ogun', 'Ondo', 'Osun', 'Oyo', 'Plateau', 'Rivers', 'Sokoto',
            'Taraba', 'Yobe', 'Zamfara',
        ];

        foreach ($states as $state) {
            $isLagos = $state === 'Lagos';

            DeliveryZone::firstOrCreate(
                ['state' => $state],
                [
                    'fee' => $isLagos ? 3000 : 5000,
                    'delivery_period' => $isLagos ? '1-2 business days' : '3-5 business days',
                    'is_active' => $isLagos,
                ]
            );
        }
    }
}
