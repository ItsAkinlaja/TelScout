<?php

namespace App\Providers;

use App\Models\EmailMessage;
use App\Models\Opportunity;
use App\Policies\EmailPolicy;
use App\Policies\OpportunityPolicy;
use App\Services\JobSources\AdzunaSource;
use App\Services\JobSources\AfricaWorkSource;
use App\Services\JobSources\ArbeitnowSource;
use App\Services\JobSources\IndeedNigeriaSource;
use App\Services\JobSources\JobSourceManager;
use App\Services\JobSources\JSearchSource;
use App\Services\JobSources\OpenWebNinjaSource;
use App\Services\JobSources\ReedSource;
use App\Services\JobSources\RemoteOkSource;
use App\Services\JobSources\RemotiveSource;
use App\Services\JobSources\SerpApiSource;
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
                // ── Free sources, no key needed ───────────────────────────
                new RemoteOkSource(),     // Global remote jobs
                new RemotiveSource(),     // Global remote jobs
                new ArbeitnowSource(),    // European + global jobs
                new TheMuseSource(),      // US-focused tech jobs

                // ── Nigerian & African sources ────────────────────────────
                new IndeedNigeriaSource(), // Indeed Nigeria (ng.indeed.com)
                new AfricaWorkSource(),    // Pan-African job board (Nigeria, Ghana, Kenya…)
            ];

            // ── Key-gated sources (enabled when .env is configured) ───────

            // SerpAPI — Google Jobs: surfaces Jobberman, LinkedIn NG, MyJobMag,
            // and every other source Google indexes. 100 free searches/month.
            // Register at https://serpapi.com
            if (config('services.serpapi.key')) {
                $sources[] = new SerpApiSource();
            }

            if (config('services.adzuna.app_id') && config('services.adzuna.app_key')) {
                $sources[] = new AdzunaSource();
            }

            if (config('services.jsearch.api_key')) {
                $sources[] = new JSearchSource();
            }

            if (config('services.reed.api_key')) {
                $sources[] = new ReedSource();
            }

            // OpenWebNinja — second Google Jobs source, runs alongside SerpAPI
            if (config('services.openwebninja.api_key')) {
                $sources[] = new OpenWebNinjaSource();
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
