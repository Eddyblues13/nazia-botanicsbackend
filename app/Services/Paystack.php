<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Paystack, wrapped so the rest of the app never deals in kobo.
 *
 * Every price in this codebase — product sizes, delivery fees, order totals —
 * is whole naira. Paystack works in kobo. That conversion happens here and
 * nowhere else; doing it twice, or not at all, is the classic way to charge
 * someone a hundredth or a hundred times the real price.
 */
class Paystack
{
    private const BASE = 'https://api.paystack.co';

    public function configured(): bool
    {
        return filled(config('services.paystack.secret'));
    }

    private function secret(): string
    {
        $secret = config('services.paystack.secret');

        if (blank($secret)) {
            throw new RuntimeException('PAYSTACK_SECRET_KEY is not set.');
        }

        return $secret;
    }

    public static function toKobo(int $naira): int
    {
        return $naira * 100;
    }

    public static function toNaira(int $kobo): int
    {
        return intdiv($kobo, 100);
    }

    /**
     * Starts a transaction and hands back the URL to send the customer to.
     *
     * The amount is taken from the order rather than from the request, so a
     * tampered client payload cannot change what is charged.
     */
    public function initialize(Order $order, string $callbackUrl): array
    {
        $response = Http::withToken($this->secret())
            ->acceptJson()
            ->timeout(20)
            ->post(self::BASE.'/transaction/initialize', [
                'email' => $order->customer_email,
                'amount' => self::toKobo($order->total),
                'currency' => 'NGN',
                'reference' => $order->payment_reference,
                'callback_url' => $callbackUrl,
                'metadata' => [
                    'order_reference' => $order->reference,
                    'customer_name' => $order->customer_name,
                    'customer_phone' => $order->customer_phone,
                    'delivery_zone' => $order->delivery_zone,
                ],
            ]);

        if ($response->failed() || ! $response->json('status')) {
            Log::error('Paystack initialize failed', [
                'order' => $order->reference,
                'status' => $response->status(),
                'body' => $response->json(),
            ]);

            throw new RuntimeException($response->json('message') ?? 'Could not start the payment.');
        }

        return $response->json('data');
    }

    /**
     * Asks Paystack what actually happened to a reference.
     *
     * This is the only source of truth the app trusts. A customer returning to
     * the callback URL proves nothing — they can land there by typing it.
     */
    public function verify(string $reference): array
    {
        $response = Http::withToken($this->secret())
            ->acceptJson()
            ->timeout(20)
            ->get(self::BASE.'/transaction/verify/'.urlencode($reference));

        if ($response->failed() || ! $response->json('status')) {
            Log::warning('Paystack verify failed', [
                'reference' => $reference,
                'status' => $response->status(),
                'body' => $response->json(),
            ]);

            throw new RuntimeException($response->json('message') ?? 'Could not verify the payment.');
        }

        return $response->json('data');
    }

    /**
     * Confirms a webhook really came from Paystack.
     *
     * Compared with hash_equals so the check cannot be timed, and against the
     * raw request body — re-encoding the parsed JSON would change the bytes and
     * every signature would fail.
     */
    public function signatureIsValid(string $rawBody, ?string $signature): bool
    {
        if (blank($signature)) {
            return false;
        }

        return hash_equals(
            hash_hmac('sha512', $rawBody, $this->secret()),
            $signature
        );
    }
}
