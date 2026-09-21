<?php

namespace Tests\Feature;

use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAuthTest extends TestCase
{
    use RefreshDatabase;

    private function admin(array $overrides = []): Admin
    {
        return Admin::create(array_merge([
            'name' => 'Nazia Owner',
            'email' => 'owner@example.com',
            'password' => 'secret-password',
            'role' => Admin::ROLE_OWNER,
            'is_active' => true,
        ], $overrides));
    }

    public function test_it_issues_a_token_for_valid_credentials(): void
    {
        $this->admin();

        $this->postJson('/api/admin/login', [
            'email' => 'owner@example.com',
            'password' => 'secret-password',
        ])->assertOk()->assertJsonStructure(['token', 'admin' => ['id', 'name', 'email', 'role']]);
    }

    public function test_it_rejects_a_wrong_password(): void
    {
        $this->admin();

        $this->postJson('/api/admin/login', [
            'email' => 'owner@example.com',
            'password' => 'not-the-password',
        ])->assertStatus(422);
    }

    public function test_it_never_returns_the_password_hash(): void
    {
        $this->admin();

        $response = $this->postJson('/api/admin/login', [
            'email' => 'owner@example.com',
            'password' => 'secret-password',
        ]);

        $this->assertStringNotContainsString('password', json_encode($response->json('admin')));
    }

    public function test_the_dashboard_is_closed_to_anonymous_callers(): void
    {
        $this->getJson('/api/admin/dashboard')->assertUnauthorized();
    }

    public function test_a_deactivated_admin_is_locked_out(): void
    {
        $admin = $this->admin(['is_active' => false]);

        // A token alone is not enough — the active check runs on every request,
        // so revoking access takes effect immediately.
        $this->actingAs($admin, 'admin')
            ->getJson('/api/admin/dashboard')
            ->assertForbidden();
    }

    public function test_a_manager_cannot_reach_team_management(): void
    {
        $manager = $this->admin([
            'email' => 'manager@example.com',
            'role' => Admin::ROLE_MANAGER,
        ]);

        $this->actingAs($manager, 'admin')->getJson('/api/admin/team')->assertForbidden();
    }

    public function test_an_owner_can_reach_team_management(): void
    {
        $this->actingAs($this->admin(), 'admin')->getJson('/api/admin/team')->assertOk();
    }

    public function test_an_admin_can_edit_their_own_name_and_email(): void
    {
        $admin = $this->admin(['name' => 'Old Name', 'email' => 'old@naziabotanics.com']);

        $this->actingAs($admin, 'admin')
            ->putJson('/api/admin/profile', [
                'name' => 'New Name',
                'email' => 'new@naziabotanics.com',
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'New Name');

        $this->assertDatabaseHas('admins', [
            'id' => $admin->id, 'name' => 'New Name', 'email' => 'new@naziabotanics.com',
        ]);
    }

    public function test_editing_a_profile_cannot_change_role_or_active_state(): void
    {
        $manager = $this->admin(['role' => 'manager', 'email' => 'mgr@naziabotanics.com']);

        // A manager posting a role alongside their name must not be promoted.
        $this->actingAs($manager, 'admin')
            ->putJson('/api/admin/profile', [
                'name' => 'Sneaky', 'email' => 'mgr@naziabotanics.com',
                'role' => 'owner', 'is_active' => true,
            ])
            ->assertOk();

        $this->assertSame('manager', $manager->fresh()->role);
    }

    public function test_a_profile_cannot_take_another_admins_email(): void
    {
        $this->admin(['email' => 'taken@naziabotanics.com']);
        $other = $this->admin(['email' => 'mine@naziabotanics.com']);

        $this->actingAs($other, 'admin')
            ->putJson('/api/admin/profile', ['name' => 'Whoever', 'email' => 'taken@naziabotanics.com'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }
}
