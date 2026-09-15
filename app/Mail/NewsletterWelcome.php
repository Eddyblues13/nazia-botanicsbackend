<?php

namespace App\Mail;

use App\Models\NewsletterSubscriber;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Sent to someone the moment they subscribe, so the first thing the list ever does is confirm it worked.
 */
class NewsletterWelcome extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public NewsletterSubscriber $subscriber)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Welcome to Nazia Botanics');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.newsletter-welcome');
    }
}
