<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\DeliveryZone;
use App\Models\Product;
use App\Services\Paystack;
use App\Support\OrderPayments;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use RuntimeException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    public function store(StoreOrderRequest $request, Paystack $paystack): JsonResponse
    {
        $data = $request->validated();

        $products = Product::query()
            ->active()
            ->whereIn('slug', collect($data['items'])->pluck('product_id')->unique())
            ->get()
            ->keyBy('slug');

        $lines = [];
        $subtotal = 0;

        foreach ($data['items'] as $index => $item) {
            $product = $products->get($item['product_id']);

            if (! $product) {
                throw ValidationException::withMessages([
                    "items.{$index}.product_id" => 'This oil is no longer available.',
                ]);
            }

            // The price comes from the size on the product, never from the
            // client — so a tampered cart cannot name its own figure.
            $unitPrice = $product->priceForSize($item['size']);

            if ($unitPrice === null) {
                throw ValidationException::withMessages([
                    "items.{$index}.size" => "{$product->name} is not sold in {$item['size']}.",
                ]);
            }

            $lineTotal = $unitPrice * $item['qty'];
            $subtotal += $lineTotal;

            // Snapshotted alongside the price so profit on a past order never
            // moves when a recipe is recosted.
            $unitCost = $product->costForSize($item['size']);

            $lines[] = [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'product_slug' => $product->slug,
                'size' => $item['size'],
                'qty' => $item['qty'],
                'unit_price' => $unitPrice,
                'line_total' => $lineTotal,
                'unit_cost' => $unitCost,
                'line_cost' => $unitCost === null ? null : $unitCost * $item['qty'],
            ];
        }

        // Delivery is priced from the zone table, so the customer cannot post
        // their own fee and the promise shown at checkout is the one charged.
        $zone = DeliveryZone::query()->active()->where('state', $data['delivery_state'])->first();

        if (! $zone) {
            throw ValidationException::withMessages([
                'delivery_state' => 'We do not deliver to that state yet.',
            ]);
        }

        $deliveryFee = $zone->fee;
        $total = $subtotal + $deliveryFee;

        $order = DB::transaction(function () use ($data, $lines, $subtotal, $zone, $deliveryFee, $total) {
            $order = Order::create([
                'reference' => Order::generateReference(),
                'customer_name' => $data['customer_name'],
                'customer_phone' => $data['customer_phone'],
                'customer_email' => $data['customer_email'] ?? null,
                'delivery_address' => $data['delivery_address'],
                'note' => $data['note'] ?? null,
                'subtotal' => $subtotal,
                'status' => Order::STATUS_PENDING,
                // Copied onto the order, not joined, so re-pricing a zone
                // later never rewrites what a past customer was charged.
                'delivery_state' => $zone->state,
                'delivery_fee' => $deliveryFee,
                'delivery_period' => $zone->delivery_period,
                'total' => $total,
                'payment_status' => Order::PAYMENT_PENDING,
                // Distinct from the human-facing order reference: Paystack
                // rejects a reference it has seen before, and a retried
                // payment needs a fresh one.
                'payment_reference' => 'NBP-'.Str::upper(Str::random(16)),
            ]);

            $order->items()->createMany($lines);

            return $order;
        });

        $order->load('items');

        // Nothing is announced yet. The confirmation emails wait until the
        // money actually arrives, in OrderPayments::markPaid().
        try {
            $transaction = $paystack->initialize($order, $this->callbackUrl($order));
        } catch (RuntimeException $e) {
            OrderPayments::markFailed($order);

            return response()->json([
                'message' => 'We could not start the payment. Please try again.',
            ], 502);
        }

        return (new OrderResource($order))
            ->additional([
                'payment' => [
                    'authorization_url' => $transaction['authorization_url'],
                    'reference' => $order->payment_reference,
                ],
            ])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Where Paystack sends the customer once they are done.
     *
     * Their order page, which asks us to verify on arrival.
     */
    private function callbackUrl(Order $order): string
    {
        return rtrim(config('app.frontend_url'), '/')."/order/{$order->reference}";
    }

    /**
     * Confirms a payment on the customer's return.
     *
     * Landing on the callback URL proves nothing — anyone can type it — so the
     * transaction is checked against Paystack before anything is believed.
     * The webhook does the same job for customers who close the tab.
     */
    public function verifyPayment(Order $order, Paystack $paystack): JsonResponse
    {
        if ($order->payment_status === Order::PAYMENT_PAID) {
            return response()->json(['data' => new OrderResource($order->load('items'))]);
        }

        if (blank($order->payment_reference)) {
            return response()->json(['message' => 'This order has no payment to check.'], 422);
        }

        try {
            $transaction = $paystack->verify($order->payment_reference);
        } catch (RuntimeException $e) {
            return response()->json(['message' => 'We could not reach Paystack. Please refresh in a moment.'], 502);
        }

        if (($transaction['status'] ?? null) !== 'success') {
            OrderPayments::markFailed($order);

            return response()->json([
                'message' => 'That payment did not go through.',
                'data' => new OrderResource($order->load('items')),
            ], 402);
        }

        $order = OrderPayments::markPaid($order, $transaction);

        return response()->json(['data' => new OrderResource($order->load('items'))]);
    }

    public function show(Order $order): OrderResource
    {
        return new OrderResource($order->load('items'));
    }
}
