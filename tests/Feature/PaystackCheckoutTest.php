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

class PaystackCheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.paystack.secret' => 'sk_test_fake']);

        Product::create([
            'slug' => 'growth-oil',
            'name' => 'Botanical Growth Oil',
            'tagline' => 'Scalp and Hair Oil',
            'price_from' => 25000,
            'description' => 'Test',
            'sizes' => [['label' => '4 oz', 'price' => 25000]],
            'highlights' => [],
            'ingredients' => [],
            'is_active' => true,
        ]);

        DeliveryZone::create([
            'state' => 'Lagos', 'fee' => 3000,
            'delivery_period' => '1-2 business days', 'is_active' => true,
        ]);

        DeliveryZone::create([
            'state' => 'Kano', 'fee' => 5000,
            'delivery_period' => '3-5 business days', 'is_active' => false,
        ]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'customer_name' => 'Ada Obi',
            'customer_phone' => '+2348000000000',
            'customer_email' => 'ada@example.com',
            'delivery_address' => '1 Test Road, Lekki',
            'delivery_state' => 'Lagos',
            'items' => [['product_id' => 'growth-oil', 'size' => '4 oz', 'qty' => 2]],
        ], $overrides);
    }

    public function test_it_adds_the_zone_fee_and_charges_the_total_in_kobo(): void
    {
        Mail::fake();
        Http::fake(['api.paystack.co/transaction/initialize' => Http::response([
            'status' => true,
            'data' => ['authorization_url' => 'https://checkout.paystack.com/abc123'],
        ])]);

        $response = $this->postJson('/api/orders', $this->payload());

        $response->assertCreated()
            ->assertJsonPath('data.subtotal', 50000)
            ->assertJsonPath('data.delivery_fee', 3000)
            ->assertJsonPath('data.total', 53000)
            ->assertJsonPath('data.payment_status', Order::PAYMENT_PENDING)
            ->assertJsonPath('payment.authorization_url', 'https://checkout.paystack.com/abc123');

        // Naira on the order, kobo on the wire.
        Http::assertSent(fn ($request) => $request['amount'] === 5300000 && $request['currency'] === 'NGN');

        // Nothing is announced until the money lands.
        Mail::assertNothingSent();
    }

    public function test_it_refuses_a_state_the_shop_does_not_deliver_to(): void
    {
        $this->postJson('/api/orders', $this->payload(['delivery_state' => 'Kano']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('delivery_state');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_it_ignores_a_webhook_with_a_bad_signature(): void
    {
        $order = $this->makePendingOrder();

        $this->postJson('/api/paystack/webhook', ['event' => 'charge.success', 'data' => [
            'reference' => $order->payment_reference, 'amount' => 5300000,
        ]], ['x-paystack-signature' => 'not-the-real-thing'])->assertStatus(401);

        $this->assertSame(Order::PAYMENT_PENDING, $order->fresh()->payment_status);
    }

    public function test_a_signed_webhook_pays_the_order_and_emails_once(): void
    {
        Mail::fake();
        $order = $this->makePendingOrder();

        $body = json_encode(['event' => 'charge.success', 'data' => [
            'reference' => $order->payment_reference, 'amount' => 5300000, 'channel' => 'card',
        ]]);

        // Sent twice: Paystack retries, and a retry must not double-charge or
        // double-email.
        foreach ([1, 2] as $attempt) {
            $this->call('POST', '/api/paystack/webhook', [], [], [], [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_PAYSTACK_SIGNATURE' => hash_hmac('sha512', $body, 'sk_test_fake'),
            ], $body)->assertNoContent();
        }

        $order->refresh();
        $this->assertSame(Order::PAYMENT_PAID, $order->payment_status);
        $this->assertSame(Order::STATUS_CONFIRMED, $order->status);
        $this->assertSame(53000, $order->amount_paid);
        $this->assertSame('card', $order->payment_channel);

        Mail::assertSent(OrderPlaced::class, 1);
        Mail::assertSent(OrderAlert::class, 1);
    }

    public function test_verify_trusts_paystack_not_the_customer_landing_on_the_page(): void
    {
        Mail::fake();
        $order = $this->makePendingOrder();

        Http::fake(['api.paystack.co/transaction/verify/*' => Http::response([
            'status' => true,
            'data' => ['status' => 'failed', 'amount' => 0],
        ])]);

        $this->postJson("/api/orders/{$order->reference}/verify-payment")->assertStatus(402);

        $this->assertSame(Order::PAYMENT_FAILED, $order->fresh()->payment_status);
        Mail::assertNothingSent();
    }

    public function test_unpaid_orders_are_not_counted_as_revenue(): void
    {
        Mail::fake();

        // One order left unpaid, one paid.
        $abandoned = $this->makePendingOrder();
        $paid = $this->makePendingOrder();
        OrderPayments::markPaid($paid, ['amount' => 5300000, 'channel' => 'card']);

        $admin = \App\Models\Admin::create([
            'name' => 'Owner', 'email' => 'owner@test.com',
            'password' => bcrypt('password'), 'role' => 'owner', 'is_active' => true,
        ]);

        $stats = $this->actingAs($admin, 'admin')
            ->getJson('/api/admin/dashboard')
            ->assertOk()
            ->json('data');

        // Only the paid one counts, and it counts its full total including
        // delivery — not the abandoned cart sitting next to it.
        $this->assertSame($paid->total, $stats['revenue_total']);
        $this->assertSame(Order::PAYMENT_PENDING, $abandoned->fresh()->payment_status);
    }

    public function test_the_emails_show_delivery_and_the_amount_actually_paid(): void
    {
        Mail::fake();
        $order = $this->makePendingOrder();
        OrderPayments::markPaid($order, ['amount' => 5300000, 'channel' => 'card']);
        $order->refresh()->load('items');

        foreach ([OrderPlaced::class, OrderAlert::class] as $class) {
            $html = (new $class($order))->render();

            // The goods, the delivery and the total the customer was charged.
            $this->assertStringContainsString('50,000', $html, "{$class} is missing the subtotal");
            $this->assertStringContainsString('3,000', $html, "{$class} is missing the delivery fee");
            $this->assertStringContainsString('53,000', $html, "{$class} is missing the total");
            $this->assertStringContainsString('Lagos', $html, "{$class} is missing the delivery state");

            // The old copy promised a delivery quote that now arrives upfront.
            $this->assertStringNotContainsString('Delivery is quoted when we confirm', $html);
        }
    }

    private function makePendingOrder(): Order
    {
        Http::fake(['api.paystack.co/transaction/initialize' => Http::response([
            'status' => true, 'data' => ['authorization_url' => 'https://checkout.paystack.com/x'],
        ])]);

        $this->postJson('/api/orders', $this->payload())->assertCreated();

        return Order::query()->latest('id')->firstOrFail();
    }
}
