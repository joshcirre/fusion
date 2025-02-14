<?php

/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace Fusion\Console\Commands;

use Fusion\Fusion;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class PostInstall extends Command
{
    protected $signature = 'fusion:post-install';

    protected $description = 'Migrate the internal DB';

    public function handle(): void
    {
        $path = Fusion::storage('fusion.sqlite');

        if (!file_exists($path)) {
            File::ensureDirectoryExists(dirname($path));
            touch($path);
        }

        Artisan::call('migrate', [
            '--database' => '__fusion',
            '--path' => __DIR__ . '/../../../database/migrations',
            '--realpath' => true,
            '--force' => true,
        ]);

        $this->info('Fusion database migrated.');
    }
}
