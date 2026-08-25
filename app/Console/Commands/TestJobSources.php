<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\JobSources\RemoteOkSource;
use App\Services\JobSources\RemotiveSource;
use App\Services\JobSources\ArbeitnowSource;
use App\Services\JobSources\TheMuseSource;
use App\Services\JobSources\IndeedNigeriaSource;
use App\Services\JobSources\AfricaWorkSource;
use App\Services\JobSources\AdzunaSource;
use App\Services\JobSources\ReedSource;
use App\Services\JobSources\SerpApiSource;
use App\Services\JobSources\OpenWebNinjaSource;
use App\Services\JobSources\JSearchSource;

class TestJobSources extends Command
{
    protected $signature   = 'jobs:test-sources';
    protected $description = 'Test each job source individually and report results';

    private array $criteria = [
        'keywords'    => ['software engineer'],
        'locations'   => ['Lagos Nigeria'],
        'remote_only' => false,
        'days_old'    => 30,
    ];

    public function handle(): int
    {
        $this->info('');
        $this->info('=== TelScout Job Source Tests ===');
        $this->info('Query: "software engineer" | Location: "Lagos Nigeria"');
        $this->info('');

        $sources = [
            new RemoteOkSource(),
            new RemotiveSource(),
            new ArbeitnowSource(),
            new TheMuseSource(),
            new IndeedNigeriaSource(),
            new AfricaWorkSource(),
            new AdzunaSource(),
            new ReedSource(),
            new SerpApiSource(),
            new OpenWebNinjaSource(),
            new JSearchSource(),
        ];

        $passed = 0;
        $failed = 0;

        foreach ($sources as $source) {
            $name  = strtoupper($source->getName());
            $start = microtime(true);

            try {
                $results = $source->search($this->criteria);
                $count   = $results->count();
                $elapsed = round((microtime(true) - $start) * 1000);

                if ($count > 0) {
                    $sample = $results->first();
                    $this->line(
                        "  <fg=green>✔ {$name}</> — {$count} jobs in {$elapsed}ms" .
                        " | Sample: \"{$sample['title']}\" @ {$sample['company']}"
                    );
                    $passed++;
                } else {
                    $this->line("  <fg=yellow>⚠ {$name}</> — 0 jobs returned in {$elapsed}ms (key missing or no results for this query)");
                    $failed++;
                }
            } catch (\Throwable $e) {
                $elapsed = round((microtime(true) - $start) * 1000);
                $this->line("  <fg=red>✗ {$name}</> — ERROR in {$elapsed}ms: {$e->getMessage()}");
                $failed++;
            }
        }

        $this->info('');
        $this->info("=== Results: {$passed} passed, {$failed} issues ===");
        $this->info('');

        return 0;
    }
}
