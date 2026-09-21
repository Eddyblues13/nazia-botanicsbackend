<?php

namespace App\Support;

use App\Mail\OrderAlert;
use App\Mail\OrderPlaced;
use App\Models\Order;
use App\Services\Paystack;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Turning a verified Paystack transaction into a paid order.
 *
 * Two things race to call this: the customer returning to the callback URL and
 * Paystack's webhook, which can itself fire more than once. So it is written to
 * be safe to call repeatedly — the row is locked, an already-paid order returns
 * untouched, and the confirmation emails go out exactly once.
 */
class OrderPayments
{
    /**
     * @param  array  $transaction  The `data` block from a Paystack verify or webhook.
     */
    public static function markPaid(Order $order, array $transaction): Order
    {
        $justPaid = false;

        $order = DB::transaction(function () use ($order, $transaction, &$justPaid) {
            /** @var Order $fresh */
            $fresh = Order::query()->lockForUpdate()->find($order->getKey());

            if ($fresh->payment_status === Order::PAYMENT_PAID) {
                return $fresh;
            }

            $paidKobo = (int) ($transaction['amount'] ?? 0);

            $fresh->forceFill([
                'payment_status' => Order::PAYMENT_PAID,
                'payment_channel' => $transaction['channel'] ?? null,
                'amount_paid' => Paystack::toNaira($paidKobo),
                'paid_at' => now(),
                // Paid orders are confirmed: the shop has the money and only
                // needs to ship.
                'status' => Order::STATUS_CONFIRMED,
            ])->save();

            $justPaid = true;

            return $fresh;
        });

        if (! $justPaid) {
            return $order;
        }

        // A short payment still marks the order paid — the money did arrive —
        // but it is worth knowing about, because it should not be possible.
        if ($order->amount_paid < $order->total) {
            Log::warning('Order paid for less than its total', [
                'order' => $order->reference,
                'expected' => $order->total,
                'paid' => $order->amount_paid,
            ]);
        }

        $order->load('items');

        // Only once the money is in. Failures are logged, never surfaced.
        Notifier::send($order->customer_email, new OrderPlaced($order), 'order confirmation');
        Notifier::send(Notifier::team(), new OrderAlert($order), 'order alert');

        return $order;
    }

    public static function markFailed(Order $order): void
    {
        if ($order->payment_status === Order::PAYMENT_PAID) {
            return;
        }

        $order->forceFill(['payment_status' => Order::PAYMENT_FAILED])->save();
    }
}
