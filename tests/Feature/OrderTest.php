<?php

namespace Tests\Feature;

use App\Mail\OrderAlert;
use App\Mail\OrderPlaced;
use App\Models\DeliveryZone;
use App\Models\Order;
use App\Models\Product;
use App\Support\OrderPayments;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.paystack.secret' => 'sk_test_fake']);

        DeliveryZone::create([
            'state' => 'Lagos',
            'fee' => 3000,
            'delivery_period' => '1-2 business days',
            'is_active' => true,
        ]);

        // Every order now starts a payment, so the call out to Paystack is
        // stubbed rather than made.
        Http::fake(['api.paystack.co/transaction/initialize' => Http::response([
            'status' => true,
            'data' => ['authorization_url' => 'https://checkout.paystack.com/test'],
        ])]);
    }

    private function product(array $overrides = []): Product
    {
        return Product::create(array_merge([
            'slug' => 'growth-oil',
            'name' => 'Botanical Growth Oil',
            'tagline' => 'Scalp and Hair Oil',
            'price_from' => 15000,
            'description' => 'Test',
            'sizes' => [
                ['label' => '2 oz', 'price' => 15000, 'cost' => 6000],
                ['label' => '4 oz', 'price' => 25000],
            ],
            'highlights' => [],
            'ingredients' => [],
            'is_active' => true,
        ], $overrides));
    }

    private function payload(array $items): array
    {
        return [
            'customer_name' => 'Ada Obi',
            'customer_phone' => '+2348055555555',
            'customer_email' => 'ada@example.com',
            'delivery_address' => '12 Awolowo Road, Lagos',
            'delivery_state' => 'Lagos',
            'items' => $items,
        ];
    }

    public function test_it_places_an_order_and_prices_it_from_the_catalog(): void
    {
        Mail::fake();
        $this->product();

        $response = $this->postJson('/api/orders', $this->payload([
            ['product_id' => 'growth-oil', 'size' => '4 oz', 'qty' => 2],
        ]));

        $response->assertCreated()->assertJsonPath('data.subtotal', 50000);
        $this->assertDatabaseHas('order_items', ['unit_price' => 25000, 'line_total' => 50000]);
    }

    public function test_it_ignores_any_price_supplied_by_the_client(): void
    {
        Mail::fake();
        $this->product();

        $this->postJson('/api/orders', $this->payload([
            // A tampered cart naming its own figure must not be honoured.
            ['product_id' => 'growth-oil', 'size' => '4 oz', 'qty' => 1, 'unit_price' => 1, 'line_total' => 1],
        ]))->assertCreated()->assertJsonPath('data.subtotal', 25000);
    }

    public function test_it_rejects_a_size_the_product_is_not_sold_in(): void
    {
        Mail::fake();
        $this->product();

        $this->postJson('/api/orders', $this->payload([
            ['product_id' => 'growth-oil', 'size' => '9 oz', 'qty' => 1],
        ]))->assertStatus(422)->assertJsonValidationErrors('items.0.size');
    }

    public function test_it_will_not_sell_an_inactive_product(): void
    {
        Mail::fake();
        $this->product(['is_active' => false]);

        $this->postJson('/api/orders', $this->payload([
            ['product_id' => 'growth-oil', 'size' => '4 oz', 'qty' => 1],
        ]))->assertStatus(422);
    }

    public function test_it_snapshots_cost_only_where_one_is_recorded(): void
    {
        Mail::fake();
        $this->product();

        $this->postJson('/api/orders', $this->payload([
            ['product_id' => 'growth-oil', 'size' => '2 oz', 'qty' => 2],
            ['product_id' => 'growth-oil', 'size' => '4 oz', 'qty' => 1],
        ]))->assertCreated();

        // The costed size carries its cost; the uncosted one stays null rather
        // than defaulting to zero, which would read as "free to make".
        $this->assertDatabaseHas('order_items', ['size' => '2 oz', 'unit_cost' => 6000, 'line_cost' => 12000]);
        $this->assertDatabaseHas('order_items', ['size' => '4 oz', 'unit_cost' => null, 'line_cost' => null]);
    }

    public function test_it_emails_the_customer_and_the_team_once_the_payment_lands(): void
    {
        Mail::fake();
        $this->product();

        $this->postJson('/api/orders', $this->payload([
            ['product_id' => 'growth-oil', 'size' => '4 oz', 'qty' => 1],
        ]))->assertCreated();

        // Placing the order announces nothing — it is not paid for yet.
        Mail::assertNothingSent();

        OrderPayments::markPaid(Order::query()->latest('id')->firstOrFail(), [
            'amount' => 2800000, 'channel' => 'card',
        ]);

        Mail::assertSent(OrderPlaced::class, fn ($m) => $m->hasTo('ada@example.com'));
        Mail::assertSent(OrderAlert::class);
    }

    public function test_a_failing_mailer_does_not_lose_the_payment(): void
    {
        $this->product();

        $this->postJson('/api/orders', $this->payload([
            ['product_id' => 'growth-oil', 'size' => '4 oz', 'qty' => 1],
        ]))->assertCreated();

        Mail::shouldReceive('to')->andThrow(new \RuntimeException('SMTP down'));

        // The money has arrived; a dead mail server must not undo that.
        OrderPayments::markPaid(Order::query()->latest('id')->firstOrFail(), [
            'amount' => 2800000, 'channel' => 'card',
        ]);

        $this->assertSame(1, Order::count());
        $this->assertSame(Order::PAYMENT_PAID, Order::query()->latest('id')->firstOrFail()->payment_status);
    }
}
