<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Mail\OrderAlert;
use App\Mail\OrderPlaced;
use App\Models\Product;
use App\Support\Notifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    public function store(StoreOrderRequest $request): JsonResponse
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

        $order = DB::transaction(function () use ($data, $lines, $subtotal) {
            $order = Order::create([
                'reference' => Order::generateReference(),
                'customer_name' => $data['customer_name'],
                'customer_phone' => $data['customer_phone'],
                'customer_email' => $data['customer_email'] ?? null,
                'delivery_address' => $data['delivery_address'],
                'note' => $data['note'] ?? null,
                'subtotal' => $subtotal,
                'status' => Order::STATUS_PENDING,
            ]);

            $order->items()->createMany($lines);

            return $order;
        });

        $order->load('items');

        // Sent after the transaction commits, so nothing is ever announced that
        // did not actually save. Failures are logged, never surfaced — the sale
        // is already made.
        Notifier::send($order->customer_email, new OrderPlaced($order), 'order confirmation');
        Notifier::send(Notifier::team(), new OrderAlert($order), 'order alert');

        return (new OrderResource($order))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Order $order): OrderResource
    {
        return new OrderResource($order->load('items'));
    }
}
