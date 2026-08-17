<?php

namespace App\Console\Commands;

use App\Jobs\DiscoverJobsJob;
use App\Models\AutomationSettings;
use App\Models\SearchRun;
use App\Models\User;
use Illuminate\Console\Command;

class DiscoverJobs extends Command
{
    protected $signature   = 'jobs:discover {--user= : User ID (defaults to all users with discovery enabled)}';
    protected $description = 'Run automated job discovery for all eligible users';

    public function handle(): int
    {
        $query = AutomationSettings::where('discovery_enabled', true)->with('user');

        if ($userId = $this->option('user')) {
            $query->where('user_id', $userId);
        }

        $settings = $query->get();

        if ($settings->isEmpty()) {
            $this->info('No users with discovery enabled.');
            return self::SUCCESS;
        }

        foreach ($settings as $setting) {
            $run = SearchRun::create([
                'user_id'  => $setting->user_id,
                'provider' => 'remoteok',
                'criteria' => [
                    'keywords'  => $setting->search_keywords ?? ['react', 'laravel', 'full-stack'],
                    'locations' => $setting->search_locations ?? ['Remote'],
                    'remote_only'=> $setting->remote_only,
                ],
                'status'   => 'pending',
            ]);

            DiscoverJobsJob::dispatch($run->id, $setting->user_id);
            $this->info("Queued discovery for user #{$setting->user_id}");
        }

        return self::SUCCESS;
    }
}
