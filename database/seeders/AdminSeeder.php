<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    /**
     * Creates the first owner account. Credentials come from the environment
     * so production never ships with a known password; the local defaults are
     * meant to be changed on first sign-in.
     */
    public function run(): void
    {
        $email = env('ADMIN_EMAIL', 'admin@naziabotanics.com');

        Admin::firstOrCreate(
            ['email' => $email],
            [
                'name' => env('ADMIN_NAME', 'Nazia Owner'),
                'password' => env('ADMIN_PASSWORD', 'password'),
                'role' => Admin::ROLE_OWNER,
                'is_active' => true,
            ],
        );
    }
}
