<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prices are deliberately absent — the server reads them from the products
     * table so a tampered cart cannot set its own totals.
     */
    public function rules(): array
    {
        return [
            'customer_name' => ['required', 'string', 'max:120'],
            'customer_phone' => ['required', 'string', 'max:40'],
            // Required now that every order is paid for: Paystack needs an
            // address to send the receipt to, and so do we.
            'customer_email' => ['required', 'email', 'max:180'],
            'delivery_address' => ['required', 'string', 'max:500'],
            // Must be a state the shop currently delivers to; the fee and
            // period are read from that zone, never from the request.
            'delivery_state' => [
                'required', 'string', 'max:60',
                Rule::exists('delivery_zones', 'state')->where('is_active', true),
            ],
            'note' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.product_id' => ['required', 'string', 'exists:products,slug'],
            'items.*.size' => ['required', 'string', 'max:20'],
            'items.*.qty' => ['required', 'integer', 'min:1', 'max:99'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Your cart is empty.',
            'items.*.product_id.exists' => 'One of the oils in your cart is no longer available.',
            'customer_email.required' => 'We need an email to send your receipt to.',
            'delivery_state.required' => 'Please choose the state we are delivering to.',
            'delivery_state.exists' => 'We do not deliver to that state yet.',
        ];
    }
}
