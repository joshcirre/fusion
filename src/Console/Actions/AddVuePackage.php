<?php

namespace Fusion\Console\Actions;

use Illuminate\Console\Concerns\InteractsWithIO;
use Illuminate\Support\Facades\File;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AddVuePackage
{
    use InteractsWithIO;

    private string $packagePath;

    private string $backupPath;

    private string $packageName = '@fusion/vue';

    private string $packageVersion = 'file:vendor/fusionphp/fusion/packages/vue';

    private string $fusionInstallScript = "node -e \"try { if (!require('fs').existsSync('vendor/fusionphp/fusion/packages/vue')) { console.warn('\\x1b[33m%s\\x1b[0m', '⚠️  Fusion package not found - skipping dependency installation'); process.exit(0); } console.log('\\x1b[36m%s\\x1b[0m', '📦 Installing Fusion dependencies...'); require('child_process').execSync('cd vendor/fusionphp/fusion/packages/vue && npm install', {stdio: 'inherit'}); console.log('\\x1b[32m%s\\x1b[0m', '✅ Fusion dependencies installed successfully'); } catch (error) { console.error('\\x1b[31m%s\\x1b[0m', '❌ Error installing Fusion dependencies:'); console.error('\\x1b[31m%s\\x1b[0m', error.message); process.exit(1); }\"";

    public function __construct(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;
        $this->packagePath = base_path('package.json');
        $this->backupPath = base_path('package.json.backup');
    }

    public function handle()
    {
        if (!File::exists($this->packagePath)) {
            $this->error('[Package] package.json not found in the project root!');

            return 1;
        }

        $content = File::get($this->packagePath);
        $json = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error('[Package] Invalid JSON in package.json!');

            return 1;
        }

        // Create backup before making any changes
        File::copy($this->packagePath, $this->backupPath);

        try {
            $modified = false;

            // Add package to devDependencies if needed
            if (!$this->isPackageAlreadyInstalled($json)) {
                if (!isset($json['devDependencies'])) {
                    $json['devDependencies'] = [];
                }

                $json['devDependencies'][$this->packageName] = $this->packageVersion;
                ksort($json['devDependencies']);
                $modified = true;
            }

            // Add scripts if needed
            if ($this->addScripts($json)) {
                $modified = true;
            }

            if ($modified) {
                // Write back to file with consistent formatting
                $newContent = json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                File::put($this->packagePath, $newContent . "\n");
                $this->info('[Package] Successfully updated package.json');
                $this->info('[Package] Backup created at package.json.backup');
            } else {
                $this->info('[Package] No changes needed in package.json');
                File::delete($this->backupPath);
            }

            return 0;
        } catch (\Exception $e) {
            $this->restoreBackup($e->getMessage());

            return 1;
        }
    }

    private function addScripts(array &$json): bool
    {
        $modified = false;

        // Ensure scripts section exists
        if (!isset($json['scripts'])) {
            $json['scripts'] = [];
        }

        // Add fusion:install script if it doesn't exist
        if (!isset($json['scripts']['fusion:install'])) {
            $json['scripts']['fusion:install'] = $this->fusionInstallScript;
            $modified = true;
        }

        // Handle postinstall script
        if (!isset($json['scripts']['postinstall'])) {
            // No existing postinstall, just add ours
            $json['scripts']['postinstall'] = 'npm run fusion:install';
            $modified = true;
        } else {
            // Check if fusion:install is already in postinstall
            $currentPostinstall = $json['scripts']['postinstall'];
            if (!str_contains($currentPostinstall, 'npm run fusion:install')) {
                // Append our command to existing postinstall
                $json['scripts']['postinstall'] = "$currentPostinstall && npm run fusion:install";

                $modified = true;
            }
        }

        return $modified;
    }

    protected function isPackageAlreadyInstalled(array $json): bool
    {
        return isset($json['devDependencies'][$this->packageName]) &&
            $json['devDependencies'][$this->packageName] === $this->packageVersion;
    }

    protected function restoreBackup(string $errorMessage): void
    {
        if (File::exists($this->backupPath)) {
            File::copy($this->backupPath, $this->packagePath);
            $this->error('[Package] An error occurred. The original file has been restored from backup.');
            $this->error($errorMessage);
        }
    }
}
