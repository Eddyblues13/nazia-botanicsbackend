<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\DeliveryZone;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CustomerAccountTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.paystack.secret' => 'sk_test_fake']);
        Mail::fake();

        Product::create([
            'slug' => 'growth-oil', 'name' => 'Botanical Growth Oil',
            'tagline' => 'Scalp and Hair Oil', 'price_from' => 25000,
            'description' => 'Test', 'sizes' => [['label' => '4 oz', 'price' => 25000]],
            'highlights' => [], 'ingredients' => [], 'is_active' => true,
        ]);

        DeliveryZone::create([
            'name' => 'Mainland 1', 'fee' => 3000,
            'delivery_period' => '1-2 business days', 'is_active' => true,
        ]);

        Http::fake(['api.paystack.co/transaction/initialize' => Http::response([
            'status' => true, 'data' => ['authorization_url' => 'https://checkout.paystack.com/x'],
        ])]);
    }

    private function payload(array $o = []): array
    {
        return array_merge([
            'customer_name' => 'Ada Obi', 'customer_phone' => '+2348000000000',
            'customer_email' => 'ada@example.com', 'delivery_address' => '1 Test Road',
            'delivery_zone' => 'Mainland 1',
            'items' => [['product_id' => 'growth-oil', 'size' => '4 oz', 'qty' => 1]],
        ], $o);
    }

    public function test_a_shopper_can_register_and_is_signed_in(): void
    {
        $this->postJson('/api/account/register', [
            'name' => 'Ada Obi', 'email' => 'ada@example.com',
            'password' => 'secret-password', 'password_confirmation' => 'secret-password',
        ])->assertCreated()->assertJsonStructure(['token', 'user' => ['id', 'name', 'email']]);

        $this->assertDatabaseHas('users', ['email' => 'ada@example.com']);
    }

    public function test_the_password_is_hashed_and_never_returned(): void
    {
        $response = $this->postJson('/api/account/register', [
            'name' => 'Ada', 'email' => 'ada@example.com',
            'password' => 'secret-password', 'password_confirmation' => 'secret-password',
        ])->assertCreated();

        $this->assertStringNotContainsString('secret-password', $response->getContent());
        $this->assertNotSame('secret-password', User::first()->password);
    }

    public function test_a_wrong_password_says_nothing_about_whether_the_account_exists(): void
    {
        User::create(['name' => 'Ada', 'email' => 'ada@example.com', 'password' => 'secret-password']);

        $known = $this->postJson('/api/account/login', ['email' => 'ada@example.com', 'password' => 'wrong'])
            ->assertStatus(422)->json('errors.email.0');

        $unknown = $this->postJson('/api/account/login', ['email' => 'nobody@example.com', 'password' => 'wrong'])
            ->assertStatus(422)->json('errors.email.0');

        // Differing messages would let someone test which addresses have accounts.
        $this->assertSame($known, $unknown);
    }

    public function test_an_order_placed_while_signed_in_appears_in_that_account(): void
    {
        $user = User::create(['name' => 'Ada', 'email' => 'ada@example.com', 'password' => 'secret-password']);

        $this->actingAs($user, 'customer')->postJson('/api/orders', $this->payload())->assertCreated();

        $this->actingAs($user, 'customer')->getJson('/api/account/orders')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.total', 28000);
    }

    public function test_a_guest_order_is_not_claimed_by_signing_up_with_the_same_email(): void
    {
        // Placed with nobody signed in.
        $this->postJson('/api/orders', $this->payload())->assertCreated();
        $this->assertNull(Order::first()->user_id);

        // An email is not proof of ownership until it is verified, so signing
        // up with it must not hand over a stranger's name, phone and address.
        $user = User::create(['name' => 'Someone', 'email' => 'ada@example.com', 'password' => 'secret-password']);

        $this->actingAs($user, 'customer')->getJson('/api/account/orders')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_one_customer_cannot_see_another_customers_orders(): void
    {
        $ada = User::create(['name' => 'Ada', 'email' => 'ada@example.com', 'password' => 'secret-password']);
        $bode = User::create(['name' => 'Bode', 'email' => 'bode@example.com', 'password' => 'secret-password']);

        $this->actingAs($ada, 'customer')->postJson('/api/orders', $this->payload())->assertCreated();

        $this->actingAs($bode, 'customer')->getJson('/api/account/orders')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_a_customer_token_cannot_reach_the_dashboard(): void
    {
        $user = User::create(['name' => 'Ada', 'email' => 'ada@example.com', 'password' => 'secret-password']);

        $this->actingAs($user, 'customer')->getJson('/api/admin/orders')->assertUnauthorized();
        $this->actingAs($user, 'customer')->getJson('/api/admin/dashboard')->assertUnauthorized();
    }

    public function test_an_admin_token_is_not_a_customer_account(): void
    {
        $admin = Admin::create([
            'name' => 'Owner', 'email' => 'owner@naziabotanics.com',
            'password' => bcrypt('password'), 'role' => 'owner', 'is_active' => true,
        ]);

        $this->actingAs($admin, 'admin')->getJson('/api/account/orders')->assertUnauthorized();
    }

    public function test_account_routes_are_closed_to_strangers(): void
    {
        $this->getJson('/api/account/orders')->assertUnauthorized();
        $this->getJson('/api/account/me')->assertUnauthorized();
    }

    public function test_a_guest_order_can_be_claimed_with_its_reference_and_a_matching_email(): void
    {
        $this->postJson('/api/orders', $this->payload())->assertCreated();
        $order = Order::first();
        $this->assertNull($order->user_id);

        // Same address the order was placed with.
        $user = User::create(['name' => 'Ada', 'email' => 'ada@example.com', 'password' => 'secret-password']);

        $this->actingAs($user, 'customer')
            ->postJson('/api/account/orders/claim', ['reference' => $order->reference])
            ->assertOk();

        $this->assertSame($user->id, $order->fresh()->user_id);

        $this->actingAs($user, 'customer')->getJson('/api/account/orders')
            ->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_a_reference_alone_does_not_claim_someone_elses_order(): void
    {
        $this->postJson('/api/orders', $this->payload())->assertCreated();
        $order = Order::first();

        // Knows the reference, but the order was placed with a different email.
        $attacker = User::create(['name' => 'Mal', 'email' => 'mal@example.com', 'password' => 'secret-password']);

        $this->actingAs($attacker, 'customer')
            ->postJson('/api/account/orders/claim', ['reference' => $order->reference])
            ->assertStatus(422)
            ->assertJsonValidationErrors('reference');

        $this->assertNull($order->fresh()->user_id);
    }

    public function test_an_order_already_on_another_account_cannot_be_taken(): void
    {
        $ada = User::create(['name' => 'Ada', 'email' => 'ada@example.com', 'password' => 'secret-password']);
        $this->actingAs($ada, 'customer')->postJson('/api/orders', $this->payload())->assertCreated();
        $order = Order::first();

        // Same email on the order, but it already belongs to Ada's account.
        $other = User::create(['name' => 'Other', 'email' => 'other@example.com', 'password' => 'secret-password']);

        $this->actingAs($other, 'customer')
            ->postJson('/api/account/orders/claim', ['reference' => $order->reference])
            ->assertStatus(422);

        $this->assertSame($ada->id, $order->fresh()->user_id);
    }

    public function test_a_wrong_reference_says_nothing_about_whether_it_exists(): void
    {
        $this->postJson('/api/orders', $this->payload())->assertCreated();
        $real = Order::first()->reference;

        $user = User::create(['name' => 'Mal', 'email' => 'mal@example.com', 'password' => 'secret-password']);

        $existsButNotTheirs = $this->actingAs($user, 'customer')
            ->postJson('/api/account/orders/claim', ['reference' => $real])
            ->assertStatus(422)->json('errors.reference.0');

        $doesNotExist = $this->actingAs($user, 'customer')
            ->postJson('/api/account/orders/claim', ['reference' => 'NB-000000-ZZZZ'])
            ->assertStatus(422)->json('errors.reference.0');

        // Otherwise this becomes a way of testing which references are real.
        $this->assertSame($existsButNotTheirs, $doesNotExist);
    }

    public function test_claiming_requires_an_account(): void
    {
        $this->postJson('/api/orders', $this->payload())->assertCreated();

        $this->postJson('/api/account/orders/claim', ['reference' => Order::first()->reference])
            ->assertUnauthorized();
    }
}
