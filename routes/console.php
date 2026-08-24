<?php

use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| TelScout Scheduler
|--------------------------------------------------------------------------
|
| SHARED HOSTING SETUP:
| Add ONE cron entry in cPanel (every 5 minutes):
|
|   * /5 * * * * php /home/YOUR_USER/public_html/artisan schedule:run >> /dev/null 2>&1
|
| That single entry drives everything below. Laravel handles the timing.
| No persistent queue worker needed — queue:work runs on each cron tick
| and exits cleanly after processing pending jobs.
|
*/

// ── Queue worker (runs every 5 minutes, processes pending jobs then exits)
// --stop-when-empty ensures it exits after draining the queue — safe for shared hosting
Schedule::command('queue:work --stop-when-empty --tries=3 --timeout=55')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->runInBackground()
    ->name('queue-worker');

// ── Daily job discovery at 8:00 AM
Schedule::command('jobs:discover')
    ->dailyAt('08:00')
    ->timezone('Africa/Lagos')
    ->withoutOverlapping()
    ->runInBackground();

// ── Process follow-ups every hour
Schedule::command('outreach:process-followups')
    ->hourly()
    ->withoutOverlapping()
    ->runInBackground();

// ── Fetch registered company ATS sources every 6 hours
Schedule::command('jobs:fetch-sources')
    ->everySixHours()
    ->withoutOverlapping()
    ->runInBackground();
