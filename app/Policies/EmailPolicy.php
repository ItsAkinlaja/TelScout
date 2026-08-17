<?php

namespace App\Policies;

use App\Models\EmailMessage;
use App\Models\User;

class EmailPolicy
{
    public function view(User $user, EmailMessage $email): bool
    {
        return $user->id === $email->user_id;
    }

    public function update(User $user, EmailMessage $email): bool
    {
        return $user->id === $email->user_id;
    }

    public function delete(User $user, EmailMessage $email): bool
    {
        return $user->id === $email->user_id;
    }
}
