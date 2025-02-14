<?php

/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace Fusion\Console\Actions;

use Illuminate\Console\Concerns\InteractsWithIO;
use Illuminate\Support\Facades\File;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AddVuePlugin
{
    use InteractsWithIO;

    public function __construct(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;
    }

    public function handle()
    {
        $appPath = base_path('resources/js/app.js');

        // Check if app.js exists
        if (!File::exists($appPath)) {
            $this->error('resources/js/app.js not found!');

            return 1;
        }

        $content = File::get($appPath);

        // Check if fusion is already imported
        if (str_contains($content, '@fusion/vue/vue')) {
            $this->info('[Vue] Fusion is already imported in app.js!');

            return 0;
        }

        // Create backup only if we need to make changes
        $backupPath = base_path('resources/js/app.js.backup');
        File::copy($appPath, $backupPath);

        try {
            // Add fusion import
            $content = $this->addFusionImport($content);

            // Add fusion plugin to createApp chain
            $content = $this->addFusionPlugin($content);

            // Write modified content back to file
            File::put($appPath, $content);

            $this->info('[Vue] Successfully added Fusion to app.js');
            $this->info('[Vue] Backup created at resources/js/app.js.backup');

            return 0;
        } catch (\Exception $e) {
            // Restore from backup if something goes wrong
            if (File::exists($backupPath)) {
                File::copy($backupPath, $appPath);
                $this->error('[Vue] An error occurred. The original file has been restored from backup.');
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
            throw new \Exception('Could not find import statements in app.js');
        }

        $lastImport = end($matches[0]);

        // Add fusion import after the last import
        return str_replace(
            $lastImport,
            $lastImport . "\nimport fusion from '@fusion/vue/vue';",
            $content
        );
    }

    private function addFusionPlugin(string $content): string
    {
        // Find the createApp chain
        if (!preg_match('/return createApp.*?mount\(el\);/s', $content, $matches)) {
            throw new \Exception('Could not find createApp chain in app.js');
        }

        $createAppChain = $matches[0];

        // Find the last .use() or createApp() before .mount()
        if (!preg_match('/(.+?)\.mount\(el\);$/s', $createAppChain, $matches)) {
            throw new \Exception('Could not parse createApp chain structure');
        }

        $beforeMount = $matches[1];

        // Find the base indentation of the return statement
        preg_match('/^(\s+)return createApp/m', $content, $indentMatches);
        $baseIndent = $indentMatches[1] ?? '    ';

        // Calculate the chain indentation (2 spaces more than the previous line)
        $chainIndent = $baseIndent . '  ';

        // Add .use(fusion) with proper indentation
        $modifiedChain = $beforeMount . "\n" . $chainIndent . '.use(fusion)' . "\n" . $chainIndent . '.mount(el);';

        return str_replace($createAppChain, $modifiedChain, $content);
    }
}
