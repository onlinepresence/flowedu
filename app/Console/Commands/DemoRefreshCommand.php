<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

final class DemoRefreshCommand extends Command
{
    protected $signature = 'demo:refresh';

    protected $description = 'Refresh the single-connection demo database (migrate:fresh + DemoDataSeeder). Aborts unless APP_DEMO is true.';

    public function handle(): int
    {
        // Single restriction: APP_DEMO=true in env. No database-name check.
        if (! (bool) config('college.demo_mode', false)) {
            $this->error('Aborted: APP_DEMO is not true.');

            return self::FAILURE;
        }

        $default = (string) config('database.default');
        $database = (string) (config("database.connections.{$default}.database") ?? '');

        $this->info("Refreshing demo database [{$database}] on connection [{$default}]...");

        Artisan::call('migrate:fresh', ['--force' => true]);
        $this->line(Artisan::output());

        Artisan::call('db:seed', [
            'class' => 'Database\\Seeders\\DemoDataSeeder',
            '--force' => true,
        ]);
        $this->line(Artisan::output());

        $this->info('Demo refresh complete.');

        return self::SUCCESS;
    }
}
