<?php

/**
 * @author Aaron Francis <aarondfrancis@gmail.com>
 * @link https://aaronfrancis.com
 * @link https://twitter.com/aarondfrancis
 */

namespace Fusion;

use Closure;
use Fusion\Http\Request\RequestHelper;
use Fusion\Http\Response\PendingResponse;
use Fusion\Routing\Registrar;
use Illuminate\Foundation\Application;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class FusionManager
{
    public function __construct(public Application $app)
    {
        //
    }

    public function storage($path = null): string
    {
        if ($path) {
            $path = Str::ltrim($path, DIRECTORY_SEPARATOR);
        }

        return Str::finish(config('fusion.paths.storage'), DIRECTORY_SEPARATOR) . $path;
    }

    public function request(): RequestHelper
    {
        return $this->app->make(RequestHelper::class);
    }

    public function response(): PendingResponse
    {
        return $this->app->make(PendingResponse::class);
    }

    public function page(string $uri, string $component)
    {
        return (new Registrar)->page($uri, $component);
    }

    public function pages(string $root = '/', string|Closure $directory = ''): void
    {
        $pages = config('fusion.paths.pages');

        if (is_string($directory) && is_dir($directory)) {
            if (!Str::startsWith($directory, $pages)) {
                throw new InvalidArgumentException(
                    "The directory passed to Fusion::pages() must be a subdirectory of your Pages directory: [$pages]."
                );
            }
        }

        if (is_string($directory)) {
            $directory = str($directory)->start(DIRECTORY_SEPARATOR)->prepend($pages)->value();
            $directory = function () use ($directory) {
                return Finder::create()->in($directory)->name('*.vue');
            };
        }

        $finder = call_user_func($directory);

        if (!$finder instanceof Finder) {
            throw new InvalidArgumentException(
                'Fusion::pages() $directory closure must return an instance of Finder.'
            );
        }

        $files = collect($finder->files()->getIterator())
            ->map(fn(SplFileInfo $file) => $file->getRelativePathname())
            ->toArray();

        (new Registrar)->pages($root, $files);
    }
}
