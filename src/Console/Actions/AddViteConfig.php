<?php

/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace Fusion\Console\Actions;

use Illuminate\Console\Concerns\InteractsWithIO;
use Illuminate\Support\Facades\File;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AddViteConfig
{
    use InteractsWithIO;

    public function __construct(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;
    }

    public function handle()
    {
        $configPath = base_path('vite.config.js');

        // Check if vite.config.js exists
        if (!File::exists($configPath)) {
            $this->error('[Vite] vite.config.js not found in the project root!');

            return 1;
        }

        $content = File::get($configPath);

        // Check if fusion is already imported
        if (str_contains($content, '@fusion/vue/vite')) {
            $this->info('[Vite] Fusion plugin is already imported!');

            return 0;
        }

        // Create backup only if we need to make changes
        $backupPath = base_path('vite.config.js.backup');
        File::copy($configPath, $backupPath);

        try {
            // Add fusion import
            $content = $this->addFusionImport($content);

            // Add fusion plugin to plugins array
            $content = $this->addFusionPlugin($content);

            // Write modified content back to file
            File::put($configPath, $content);

            $this->info('[Vite] Successfully added Fusion plugin to vite.config.js');
            $this->info('[Vite] Backup created at vite.config.js.backup');

            return 0;
        } catch (\Exception $e) {
            // Restore from backup if something goes wrong
            if (File::exists($backupPath)) {
                File::copy($backupPath, $configPath);
                $this->error('[Vite] An error occurred. The original file has been restored from backup.');
                $this->error($e->getMessage());
            }

            return 1;
        }
    }

    private function addFusionImport(string $content): string
    {
        // Find the last import statement
        preg_match_all('/^import .+$/m', $content, $matches);

        if (empty($matches[0])) {
            throw new \Exception('Could not find import statements in vite.config.js');
        }

        $lastImport = end($matches[0]);

        // Add fusion import after the last import
        return str_replace(
            $lastImport,
            $lastImport . "\nimport fusion from '@fusion/vue/vite';",
            $content
        );
    }

    private function addFusionPlugin(string $content): string
    {
        // Extract the plugins array content
        if (!preg_match('/plugins:\s*\[(.*?)\]/s', $content, $matches)) {
            throw new \Exception('Could not find plugins array in vite.config.js');
        }

        // Get the current indentation level
        preg_match('/^(\s+)plugins:/m', $content, $indentMatches);
        $baseIndent = $indentMatches[1] ?? '  ';
        $pluginIndent = $baseIndent . '  ';

        // Format the fusion plugin with proper indentation
        $fusionPlugin = "\n" . $pluginIndent . 'fusion(),';

        // Find the position right after the opening bracket of the plugins array
        $pluginsStart = strpos($content, 'plugins: [') + strlen('plugins: [');

        // Insert the fusion plugin
        $content = substr_replace($content, $fusionPlugin, $pluginsStart, 0);

        // Clean up any extra newlines that might have been created
        $content = preg_replace('/\n\s*\n\s*\n/', "\n\n", $content);

        // Ensure consistent spacing around brackets
        $content = preg_replace('/\[\s+\n/', "[\n", $content);
        $content = preg_replace('/,\s*\n\s*\]/', "\n" . $baseIndent . ']', $content);

        return $content;
    }
}
