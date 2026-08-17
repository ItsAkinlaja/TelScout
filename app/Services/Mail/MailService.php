<?php

namespace App\Services\Mail;

use App\Models\MailAccount;
use InvalidArgumentException;

class MailService
{
    /**
     * Resolve the correct mail provider for a given MailAccount.
     */
    public static function for(MailAccount $account): MailProviderInterface
    {
        return match ($account->provider) {
            'gmail'   => new GmailProvider($account),
            'outlook' => new OutlookProvider($account),
            'zoho', 'yahoo', 'smtp' => new SmtpProvider($account),
            default   => throw new InvalidArgumentException("Unknown mail provider: {$account->provider}"),
        };
    }

    /**
     * Get the default active mail account for a user.
     */
    public static function defaultAccount(int $userId): ?MailAccount
    {
        return MailAccount::where('user_id', $userId)
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->first();
    }
}
