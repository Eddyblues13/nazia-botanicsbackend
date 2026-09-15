<?php

namespace App\Mail;

use App\Models\ContactMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Puts a contact-form message in the team's inbox rather than leaving it to be found in the dashboard.
 */
class ContactAlert extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public ContactMessage $contact)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Message from '.$this->contact->name);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.contact-alert');
    }
}
