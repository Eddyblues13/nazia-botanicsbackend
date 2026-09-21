<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use RuntimeException;

class AdminSeeder extends Seeder
{
    /**
     * Passwords that must never reach a live dashboard.
     *
     * The first of these shipped in .env.example, which sits in a public
     * repository — so anyone reading it knew the password to any deployment
     * that seeded with the defaults.
     */
    private const FORBIDDEN = ['password', 'secret', 'admin', 'changeme', '12345678'];

    /**
     * Creates the first owner account.
     *
     * Outside local development the password must be set deliberately: a weak
     * or missing one stops the seeder rather than quietly creating an account
     * anyone could sign in to. Locally, a random password is generated and
     * printed instead of defaulting to a known one.
     */
    public function run(): void
    {
        $email = env('ADMIN_EMAIL', 'admin@naziabotanics.com');
        $password = (string) env('ADMIN_PASSWORD', '');
        $isLocal = app()->environment('local', 'testing');

        if ($this->isWeak($password)) {
            if (! $isLocal) {
                throw new RuntimeException(
                    'ADMIN_PASSWORD is missing or too weak. Set a strong one in .env before seeding '
                    .'— a known password on a live dashboard hands over every customer record.'
                );
            }

            $password = Str::password(16);
            $this->command?->warn("Generated a local admin password: {$password}");
            $this->command?->warn('Set ADMIN_PASSWORD in .env to choose your own.');
        }

        $admin = Admin::firstOrCreate(
            ['email' => $email],
            [
                'name' => env('ADMIN_NAME', 'Nazia Owner'),
                'password' => $password,
                'role' => Admin::ROLE_OWNER,
                'is_active' => true,
            ],
        );

        if (! $admin->wasRecentlyCreated) {
            $this->command?->info("Admin {$email} already exists; password left as it is.");
        }
    }

    private function isWeak(string $password): bool
    {
        return strlen($password) < 12
            || in_array(strtolower($password), self::FORBIDDEN, true);
    }
}
