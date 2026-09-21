<?php

namespace Tests\Feature;

use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_seeder_refuses_a_known_password_outside_local(): void
    {
        // The value that shipped in .env.example, which is public.
        config(['app.env' => 'production']);
        app()['env'] = 'production';
        putenv('ADMIN_PASSWORD=password');
        $_ENV['ADMIN_PASSWORD'] = 'password';

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('ADMIN_PASSWORD');

        (new AdminSeeder)->run();
    }

    public function test_the_seeder_refuses_a_short_password_outside_local(): void
    {
        config(['app.env' => 'production']);
        app()['env'] = 'production';
        putenv('ADMIN_PASSWORD=short123');
        $_ENV['ADMIN_PASSWORD'] = 'short123';

        $this->expectException(RuntimeException::class);

        (new AdminSeeder)->run();
    }

    public function test_the_example_env_carries_no_working_password_and_no_debug(): void
    {
        $example = file_get_contents(base_path('.env.example'));

        // This file is public and gets copied onto servers.
        $this->assertStringNotContainsString('ADMIN_PASSWORD=password', $example);
        $this->assertStringNotContainsString('APP_DEBUG=true', $example);
        $this->assertStringContainsString('APP_DEBUG=false', $example);
    }

    public function test_tokens_expire(): void
    {
        // A token with no expiry stays valid for ever if it leaks.
        $this->assertNotNull(config('sanctum.expiration'));
        $this->assertGreaterThan(0, config('sanctum.expiration'));
    }

    protected function tearDown(): void
    {
        putenv('ADMIN_PASSWORD');
        unset($_ENV['ADMIN_PASSWORD']);
        parent::tearDown();
    }
}
