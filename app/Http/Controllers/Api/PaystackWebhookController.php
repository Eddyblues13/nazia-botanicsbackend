<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Paystack;
use App\Support\OrderPayments;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * Paystack's server-to-server notification.
 *
 * This, not the customer's return to the site, is what the shop relies on: it
 * arrives whether or not the customer waits for the redirect, keeps retrying
 * until it gets a 200, and is signed so it can be trusted.
 */
class PaystackWebhookController extends Controller
{
    public function __invoke(Request $request, Paystack $paystack): Response
    {
        // Checked against the raw body: re-encoding the parsed JSON changes the
        // bytes and every signature would fail.
        if (! $paystack->signatureIsValid($request->getContent(), $request->header('x-paystack-signature'))) {
            Log::warning('Rejected a Paystack webhook with a bad signature', [
                'ip' => $request->ip(),
            ]);

            return response()->noContent(401);
        }

        $event = $request->input('event');
        $data = $request->input('data', []);
        $reference = $data['reference'] ?? null;

        if (blank($reference)) {
            return response()->noContent();
        }

        $order = Order::query()->where('payment_reference', $reference)->first();

        if (! $order) {
            // Not ours, or already cleaned up. Acknowledged so Paystack stops
            // retrying something we will never recognise.
            Log::info('Paystack webhook for an unknown reference', ['reference' => $reference]);

            return response()->noContent();
        }

        match ($event) {
            'charge.success' => OrderPayments::markPaid($order, $data),
            'charge.failed' => OrderPayments::markFailed($order),
            default => Log::info('Unhandled Paystack event', ['event' => $event]),
        };

        // Always 200 once the signature checks out, even for events we ignore —
        // anything else makes Paystack retry forever.
        return response()->noContent();
    }
}
