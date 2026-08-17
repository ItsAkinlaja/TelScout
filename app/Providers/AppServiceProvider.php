<?php

namespace App\Providers;

use App\Models\EmailMessage;
use App\Models\Opportunity;
use App\Policies\EmailPolicy;
use App\Policies\OpportunityPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(Opportunity::class, OpportunityPolicy::class);
        Gate::policy(EmailMessage::class, EmailPolicy::class);
    }
}
