<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Tells the team an order has landed, with everything needed to act on it.
 */
class OrderAlert extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'New order '.$this->order->reference.' — ₦'.number_format($this->order->subtotal));
    }

    public function content(): Content
    {
        return new Content(view: 'emails.order-alert');
    }
}
