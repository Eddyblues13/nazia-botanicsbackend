<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\DeliveryZone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeliveryZoneTest extends TestCase
{
    use RefreshDatabase;

    private function admin(array $overrides = []): Admin
    {
        return Admin::create(array_merge([
            'name' => 'Owner',
            'email' => 'owner'.uniqid().'@naziabotanics.com',
            'password' => bcrypt('password'),
            'role' => 'owner',
            'is_active' => true,
        ], $overrides));
    }

    private function zone(array $overrides = []): DeliveryZone
    {
        return DeliveryZone::create(array_merge([
            'state' => 'Lagos',
            'fee' => 3000,
            'delivery_period' => '1-2 business days',
            'is_active' => true,
        ], $overrides));
    }

    public function test_an_admin_can_create_a_zone(): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->postJson('/api/admin/delivery-zones', [
                'state' => 'Oyo', 'fee' => 4500,
                'delivery_period' => '2-4 business days', 'is_active' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('data.state', 'Oyo')
            ->assertJsonPath('data.fee', 4500);

        $this->assertDatabaseHas('delivery_zones', ['state' => 'Oyo', 'fee' => 4500]);
    }

    public function test_an_admin_can_update_and_delete_a_zone(): void
    {
        $zone = $this->zone();
        $admin = $this->admin();

        $this->actingAs($admin, 'admin')
            ->putJson("/api/admin/delivery-zones/{$zone->id}", [
                'state' => 'Lagos', 'fee' => 3500,
                'delivery_period' => 'Same day in Lekki', 'is_active' => true,
            ])
            ->assertOk()
            ->assertJsonPath('data.fee', 3500);

        $this->actingAs($admin, 'admin')
            ->deleteJson("/api/admin/delivery-zones/{$zone->id}")
            ->assertOk();

        $this->assertDatabaseMissing('delivery_zones', ['id' => $zone->id]);
    }

    public function test_two_zones_cannot_share_a_state(): void
    {
        $this->zone();

        $this->actingAs($this->admin(), 'admin')
            ->postJson('/api/admin/delivery-zones', [
                'state' => 'Lagos', 'fee' => 1000,
                'delivery_period' => 'x', 'is_active' => true,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('state');
    }

    public function test_a_zone_keeps_its_own_state_when_edited(): void
    {
        $zone = $this->zone();

        // Saving a zone without renaming it must not trip its own unique rule.
        $this->actingAs($this->admin(), 'admin')
            ->putJson("/api/admin/delivery-zones/{$zone->id}", [
                'state' => 'Lagos', 'fee' => 9000,
                'delivery_period' => '1 day', 'is_active' => false,
            ])
            ->assertOk();
    }

    public function test_free_delivery_is_allowed_but_a_negative_fee_is_not(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin, 'admin')
            ->postJson('/api/admin/delivery-zones', [
                'state' => 'Free Town', 'fee' => 0,
                'delivery_period' => 'Collected in person', 'is_active' => true,
            ])
            ->assertCreated();

        $this->actingAs($admin, 'admin')
            ->postJson('/api/admin/delivery-zones', [
                'state' => 'Nowhere', 'fee' => -100,
                'delivery_period' => 'x', 'is_active' => true,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('fee');
    }

    public function test_the_storefront_lists_only_active_zones(): void
    {
        $this->zone(['state' => 'Lagos', 'is_active' => true]);
        $this->zone(['state' => 'Kano', 'is_active' => false]);

        $states = $this->getJson('/api/delivery-zones')
            ->assertOk()
            ->json('data.*.state');

        // An inactive zone has no agreed price, so offering it at checkout
        // would promise a delivery nobody has costed.
        $this->assertSame(['Lagos'], $states);
    }

    public function test_zones_are_not_public_to_edit(): void
    {
        $zone = $this->zone();

        $this->postJson('/api/admin/delivery-zones', ['state' => 'Hacked'])->assertUnauthorized();
        $this->deleteJson("/api/admin/delivery-zones/{$zone->id}")->assertUnauthorized();

        $this->assertDatabaseHas('delivery_zones', ['id' => $zone->id]);
    }
}
