<?php

namespace App\Services\Mail;

use App\Models\EmailMessage;

interface MailProviderInterface
{
    /**
     * Send an email. Returns the provider's message ID on success.
     */
    public function send(EmailMessage $email): string;

    /**
     * Test the connection / credentials without sending.
     */
    public function verify(): bool;

    /**
     * Human-readable provider name.
     */
    public function getName(): string;
}
