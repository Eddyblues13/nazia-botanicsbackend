<?php

namespace App\Mail;

use App\Models\WaitlistSignup;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Notes a new waitlist signup for the team.
 */
class WaitlistAlert extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public WaitlistSignup $signup)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'New waitlist signup');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.waitlist-alert');
    }
}
