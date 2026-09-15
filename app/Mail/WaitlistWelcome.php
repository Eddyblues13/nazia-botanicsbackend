<?php

namespace App\Mail;

use App\Models\WaitlistSignup;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Confirms a waitlist place, so a signup is never met with silence.
 */
class WaitlistWelcome extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public WaitlistSignup $signup)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'You are on the waitlist');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.waitlist-welcome');
    }
}
