<?php

namespace App\Providers;

use App\Models\EmailMessage;
use App\Models\Opportunity;
use App\Policies\EmailPolicy;
use App\Policies\OpportunityPolicy;
use App\Services\JobSources\AdzunaSource;
use App\Services\JobSources\ArbeitnowSource;
use App\Services\JobSources\JobSourceManager;
use App\Services\JobSources\JSearchSource;
use App\Services\JobSources\ReedSource;
use App\Services\JobSources\RemoteOkSource;
use App\Services\JobSources\RemotiveSource;
use App\Services\JobSources\TheMuseSource;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Laravel\Socialite\Facades\Socialite;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // JobSourceManager is used for generic aggregator sources that support
        // keyword-based searches (RemoteOk, Remotive, Arbeitnow, TheMuse, Adzuna).
        //
        // Company-specific ATS sources (Greenhouse, Lever, Ashby) are NOT wired
        // through the manager because they require per-company configuration
        // (board tokens, company slugs, organisation IDs). Those run through
        // their own dedicated queue jobs: FetchGreenhouseJobs, FetchLeverJobs,
        // FetchAshbyJobs — dispatched by FetchJobSourceJobs (scheduler) or
        // manually via JobSourceController::trigger().
        $this->app->singleton(JobSourceManager::class, function () {
            $sources = [
                new RemoteOkSource(),
                new RemotiveSource(),
                new ArbeitnowSource(),
                new TheMuseSource(),
            ];

            if (config('services.adzuna.app_id') && config('services.adzuna.app_key')) {
                $sources[] = new AdzunaSource();
            }

            if (config('services.jsearch.api_key')) {
                $sources[] = new JSearchSource();
            }

            if (config('services.reed.api_key')) {
                $sources[] = new ReedSource();
            }

            return new JobSourceManager($sources);
        });
    }

    public function boot(): void
    {
        Gate::policy(Opportunity::class, OpportunityPolicy::class);
        Gate::policy(EmailMessage::class, EmailPolicy::class);

        // Register a 'google' Socialite driver that reads from services.google_login
        // (separate from the Gmail OAuth driver used for mail account connections).
        Socialite::extend('google', function () {
            $config = config('services.google_login');
            return Socialite::buildProvider(
                \Laravel\Socialite\Two\GoogleProvider::class,
                $config
            );
        });
    }
}
