<?php

namespace App\Services\Mail;

use App\Models\EmailMessage;
use App\Models\MailAccount;
use Illuminate\Support\Facades\Mail;
use Illuminate\Mail\Message;
use RuntimeException;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Mailer as SymfonyMailer;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;

/**
 * Generic SMTP provider — works for Zoho, Yahoo, custom SMTP, any provider.
 *
 * Zoho SMTP settings:
 *   Host: smtp.zoho.com  Port: 587  Encryption: tls
 *
 * Yahoo SMTP:
 *   Host: smtp.mail.yahoo.com  Port: 587  Encryption: tls
 *
 * Custom / company SMTP: whatever the user's IT team provides.
 */
class SmtpProvider implements MailProviderInterface
{
    public function __construct(private MailAccount $account) {}

    public function getName(): string
    {
        return match ($this->account->provider) {
            'zoho'  => 'Zoho Mail',
            'yahoo' => 'Yahoo Mail',
            'smtp'  => 'SMTP',
            default => ucfirst($this->account->provider),
        };
    }

    public function send(EmailMessage $email): string
    {
        $mailer = $this->buildMailer();

        $message = (new Email())
            ->from(new Address($this->account->email ?? '', ''))
            ->to(new Address($email->recipient_email, $email->recipient_name ?? ''))
            ->subject($email->subject)
            ->text($email->body_text ?? strip_tags($email->body_html ?? ''));

        $mailer->send($message);

        return 'smtp-' . uniqid();
    }

    public function verify(): bool
    {
        try {
            $transport = Transport::fromDsn($this->buildDsn());
            $transport->start();
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    private function buildMailer(): SymfonyMailer
    {
        return new SymfonyMailer(Transport::fromDsn($this->buildDsn()));
    }

    private function buildDsn(): string
    {
        $host       = $this->account->smtp_host ?? '';
        $port       = $this->account->smtp_port ?? 587;
        $encryption = $this->account->smtp_encryption ?? 'tls';
        $username   = urlencode($this->account->getSmtpUsername() ?? '');
        $password   = urlencode($this->account->getSmtpPassword() ?? '');

        if (empty($host) || empty($username) || empty($password)) {
            throw new RuntimeException('SMTP credentials are incomplete. Configure them in Settings → Mail.');
        }

        return "smtp://{$username}:{$password}@{$host}:{$port}?encryption={$encryption}";
    }
}
