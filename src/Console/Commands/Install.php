<?php

/**
 * @author Aaron Francis <aarondfrancis@gmail.com>
 * @link https://aaronfrancis.com
 * @link https://twitter.com/aarondfrancis
 */

namespace Fusion\Console\Commands;

use Fusion\Console\Actions\AddViteConfig;
use Fusion\Console\Actions\AddVuePackage;
use Fusion\Console\Actions\AddVuePlugin;
use Fusion\Console\Actions\ModifyComposer;
use Fusion\Console\Actions\RunPackageInstall;
use Fusion\Fusion;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class Install extends Command
{
    protected $signature = 'fusion:install';

    protected $description = 'Install the Fusion service provider';

    public function handle()
    {
        $this->comment('Publishing Fusion configuration...');
        $this->callSilent('vendor:publish', ['--tag' => 'fusion-config']);

        File::ensureDirectoryExists(Fusion::storage());

        $this->action(AddVuePackage::class)->handle();
        $this->action(AddViteConfig::class)->handle();
        $this->action(AddVuePlugin::class)->handle();
        $this->action(ModifyComposer::class)->handle();
        $this->action(RunPackageInstall::class)->handle();

        Artisan::call('fusion:post-install');

        $this->info('Fusion installed successfully.');
    }

    protected function action($class)
    {
        return app()->make($class, [
            'input' => $this->input,
            'output' => $this->output,
        ]);
    }
}
