<?php

namespace Fusion\Console\Actions;

use Illuminate\Console\Concerns\InteractsWithIO;
use Illuminate\Support\Facades\File;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class RunPackageInstall
{
    use InteractsWithIO;

    public function __construct(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;
    }

    public function handle()
    {
        if (!File::exists(base_path('package.json'))) {
            $this->error('[Package] package.json not found in the project root!');

            return 1;
        }

        // Determine if project uses Yarn or npm
        $packageManager = $this->determinePackageManager();

        $this->info("[Package] Running {$packageManager} install...");

        $process = new Process(
            [$packageManager, 'install'], base_path(),
        );

        try {
            $process->run(function ($type, $buffer) {
                // Output progress in real-time
                if ($type === Process::ERR) {
                    $this->error($buffer);
                } else {
                    $this->line($buffer);
                }
            });

            if (!$process->isSuccessful()) {
                $this->error("[Package] Failed to run {$packageManager} install");
                $this->error($process->getErrorOutput());

                return 1;
            }

            $this->info("[Package] Successfully ran {$packageManager} install");

            return 0;
        } catch (\Exception $e) {
            $this->error("[Package] An error occurred while running {$packageManager} install");
            $this->error($e->getMessage());

            return 1;
        }
    }

    private function determinePackageManager(): string
    {
        // Check for yarn.lock first as it's more specific
        if (File::exists(base_path('yarn.lock'))) {
            return 'yarn';
        }

        // Default to npm
        return 'npm';
    }
}
