<?php

/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace Fusion\Console\Actions;

use Fusion\Fusion;
use Illuminate\Console\Concerns\InteractsWithIO;
use Illuminate\Support\Facades\File;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class ModifyComposer
{
    use InteractsWithIO;

    private string $composerPath;

    private string $backupPath;

    public string $postInstallCommand = '@php artisan fusion:post-install';

    public function __construct(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;
        $this->composerPath = base_path('composer.json');
        $this->backupPath = base_path('composer.json.backup');
    }

    public function handle()
    {
        if (!File::exists($this->composerPath)) {
            $this->error('[Composer] composer.json not found in the project root!');

            return 1;
        }

        $content = File::get($this->composerPath);
        $json = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error('[Composer] Invalid JSON in composer.json!');

            return 1;
        }

        File::copy($this->composerPath, $this->backupPath);

        try {
            $modified = false;

            // Add Generated namespace
            $modified = $this->addGeneratedNamespace($json) || $modified;

            // Update composer scripts
            $modified = $this->updateComposerScripts($json) || $modified;

            if (!$modified) {
                $this->info('[Composer] Composer.json is already up to date!');

                return 0;
            }

            $newContent = json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            File::put($this->composerPath, $newContent . "\n");

            $this->info('[Composer] Successfully updated composer.json');
            $this->info('[Composer] Backup created at composer.json.backup');

            $this->runComposerDumpAutoload();

            return 0;
        } catch (\Exception $e) {
            $this->restoreBackup($e->getMessage());

            return 1;
        }
    }

    private function addGeneratedNamespace(array &$json): bool
    {
        $namespace = 'Fusion\\Generated\\';
        $path = Fusion::storage('PHP');

        File::ensureDirectoryExists($path);

        $path = str($path)->replaceFirst(base_path(), '')->chopStart('/')->value();

        $currentPath = $json['autoload']['psr-4'][$namespace] ?? null;

        if ($currentPath === $path) {
            $this->info('[Composer] Fusion PSR-4 autoload entry already exists with correct path.');

            return false;
        }

        $json['autoload']['psr-4'][$namespace] = $path;

        return true;
    }

    private function updateComposerScripts(array &$json): bool
    {
        if (!isset($json['scripts'])) {
            $json['scripts'] = [];
        }

        $modified = false;
        $modified = $this->updateScript($json, 'post-update-cmd') || $modified;
        $modified = $this->updateScript($json, 'post-install-cmd') || $modified;

        return $modified;
    }

    private function updateScript(array &$json, string $scriptName): bool
    {
        if (!isset($json['scripts'][$scriptName])) {
            $json['scripts'][$scriptName] = [$this->postInstallCommand];

            return true;
        }

        if (is_string($json['scripts'][$scriptName])) {
            if ($json['scripts'][$scriptName] !== $this->postInstallCommand) {
                $json['scripts'][$scriptName] = [$json['scripts'][$scriptName], $this->postInstallCommand];

                return true;
            }
        }

        if (is_array($json['scripts'][$scriptName]) && !in_array($this->postInstallCommand,
            $json['scripts'][$scriptName])) {
            $json['scripts'][$scriptName][] = $this->postInstallCommand;

            return true;
        }

        return false;
    }

    private function runComposerDumpAutoload(): void
    {
        $this->info('[Composer] Running composer dump-autoload');

        $process = Process::fromShellCommandline('composer dump-autoload', base_path());
        $process->run();

        if (!$process->isSuccessful()) {
            $this->error('[Composer] Failed to run composer dump-autoload');
            $this->error($process->getErrorOutput());
        }
    }

    private function restoreBackup(string $errorMessage): void
    {
        if (File::exists($this->backupPath)) {
            File::copy($this->backupPath, $this->composerPath);
            $this->error('An error occurred. The original file has been restored from backup.');
            $this->error($errorMessage);
        }
    }
}
