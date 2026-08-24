<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MagicLinkMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $loginUrl,
        public readonly string $email,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your TelScout sign-in link');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.magic-link', with: [
            'loginUrl' => $this->loginUrl,
            'email'    => $this->email,
        ]);
    }
}
