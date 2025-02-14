<?php

/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace Fusion\Routing;

use Exception;
use Fusion\Http\Controllers\FusionController;
use Fusion\Models\Component;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class Registrar
{
    public function pages(string $root, array $files): void
    {
        $this->mapFiles($root, $files)->each(function ($config, $src) {
            $route = Route::any($config['uri'], [FusionController::class, 'handle'])
                // There might be a cleaner way to do this, but I think this sets us up for "know
                // nothing" route caching quite nicely. Will have to revisit to be sure.
                ->defaults('__component', $config['component'])
                ->defaults('__class', Component::where('src', $src)->first()?->php_class);

            if ($config['variadic'] !== null) {
                $route->where($config['variadic'], '.*')
                    ->defaults('__variadic', $config['variadic']);
            }
        });
    }

    public function mapFiles(string $root, array $files): Collection
    {
        return collect($files)
            ->mapWithKeys(fn($relative, $absolute) => [
                Str::chopStart($absolute, base_path()) => $relative
            ])
            ->map(function ($view) use ($root) {
                $uri = str($view)
                    ->chopEnd('.vue')
                    ->replace(DIRECTORY_SEPARATOR, '/')
                    ->lower()
                    ->start('/')
                    ->start($root)
                    ->chopEnd('/index');

                $matches = $uri->matchAll('/\[([^\]]+)\]/');

                $variadic = null;

                foreach ($matches as $match) {
                    $name = str($match)->after('...')->lower()->value();

                    if (Str::startsWith($match, '...')) {
                        if ($match !== $matches->last()) {
                            throw new Exception('Wildcard path must be last.');
                        }

                        $variadic = $name;
                    }

                    $uri = $uri->replace("[$match]", "{{$name}}");
                }

                return [
                    'uri' => $uri->value(),
                    'component' => str($view)->chopStart('/')->beforeLast('.')->value(),
                    'variadic' => $variadic,
                ];
            })
            ->sortBy(function ($route, $path) {
                // Split URI into segments and remove empty values
                $segments = array_filter(explode('/', $route['uri']));
                $segmentCount = count($segments);

                // Determine if this is an index route (empty URI)
                $isIndex = $route['uri'] === '';

                // Check if this is a root-level route
                $isRootLevel = $segmentCount <= 1;

                // Calculate route type priority
                // 0: index route ('')
                // 1: static routes with no parameters (/episodes, /podcasts)
                // 2: dynamic routes (/{podcast}, /episodes/{episode})
                // 3: variadic routes in nested paths (/episodes/{wild})
                // 4: root-level variadic routes (/{rest}) - these must come last
                $routeType = match (true) {
                    $isIndex => 0,
                    $isRootLevel && $route['variadic'] !== null => 4,
                    $route['variadic'] !== null => 3,
                    str_contains($route['uri'], '{') => 2,
                    default => 1
                };

                // For static and dynamic routes, sort by segment depth first
                // This ensures /episodes comes before /episodes/create
                return [
                    $routeType,
                    $routeType === 1 ? $segmentCount : 0,  // Only use segment count for static routes
                    $route['uri']
                ];
            });
    }

    public function page($uri, $component): \Illuminate\Routing\Route
    {
        $src = str($component)
            ->append('.vue')
            ->start(DIRECTORY_SEPARATOR)
            ->start(config('fusion.paths.pages'))
            ->chopStart(base_path())
            ->value();

        return Route::any($uri, [FusionController::class, 'handle'])
            // There might be a cleaner way to do this, but I think this sets us up for "know
            // nothing" route caching quite nicely. Will have to revisit to be sure.
            ->defaults('__component', $component)
            ->defaults('__class', Component::where('src', $src)->first()?->php_class);
    }
}
