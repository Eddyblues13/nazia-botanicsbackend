<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * The customer's copy of the order: their reference, what they bought and where it is going.
 */
class OrderPlaced extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Order '.$this->order->reference.' — Nazia Botanics');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.order-placed');
    }
}
