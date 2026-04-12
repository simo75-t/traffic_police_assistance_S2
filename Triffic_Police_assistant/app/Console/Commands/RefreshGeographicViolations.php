<?php

namespace App\Console\Commands;

use Database\Seeders\Support\GeographicViolationGenerator;
use Illuminate\Console\Command;

class RefreshGeographicViolations extends Command
{
    protected $signature = 'violations:refresh-geographic
        {count=500 : Number of geographic faker violations to generate}
        {--keep : Keep existing faker-generated geographic violations and only upsert the requested count}';

    protected $description = 'Clear and rebuild the geographic faker violations used for nationwide heatmap coverage.';

    public function handle(): int
    {
        $count = max(1, (int) $this->argument('count'));

        if (! $this->option('keep')) {
            $deleted = GeographicViolationGenerator::clear();
            $this->info(sprintf(
                'Cleared faker geography data: %d violations, %d attachments, %d locations, %d vehicles.',
                $deleted['violations'],
                $deleted['attachments'],
                $deleted['locations'],
                $deleted['vehicles'],
            ));
        }

        GeographicViolationGenerator::seed($count);
        $this->info("Seeded {$count} geographic faker violations.");

        return self::SUCCESS;
    }
}
