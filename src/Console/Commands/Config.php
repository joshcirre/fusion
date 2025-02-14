<?php

/**
 * @author Aaron Francis <aarondfrancis@gmail.com>
 * @link https://aaronfrancis.com
 * @link https://twitter.com/aarondfrancis
 */

namespace Fusion\Console\Commands;

use Fusion\Fusion;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class Config extends Command
{
    protected $signature = 'fusion:config';

    protected $description = 'Print the config for JavaScript.';

    public function handle(): void
    {
        $config = array_replace_recursive(config('fusion'), [
            // Add a few values that are useful in JavaScript but
            // not present in the config.
            'paths' => [
                'base' => base_path(),
                'config' => config_path('fusion.php'),
                'database' => Fusion::storage('fusion.sqlite'),
                'relativeJsRoot' => Str::after(config('fusion.paths.js'), base_path()),
                'jsStorage' => Fusion::storage('JavaScript'),
                'phpStorage' => Fusion::storage('PHP'),
            ],
        ]);

        $camel = [];

        foreach ($config as $key => $value) {
            if ($value instanceof \BackedEnum) {
                $value = $value->value;
            }

            // Convert the key to camel case to match JavaScript conventions.
            $camel[Str::camel($key)] = $value;
        }

        echo json_encode($camel);
    }
}
