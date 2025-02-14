<?php

/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace Fusion\Http\Request;

use Fusion\Attributes\Middleware;
use Fusion\Fusion;
use Fusion\FusionPage;
use Illuminate\Routing\Pipeline;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Support\Arr;
use ReflectionAttribute;
use ReflectionMethod;

/**
 * @mixin RequestHelper
 */
trait RunsSyntheticActions
{
    public function runSyntheticStack(array $stack)
    {
        $stack = $this->addUserMethodsToStack($stack);

        foreach ($stack as $frame) {
            $response = $this->runSyntheticAction(
                $frame['handler'], Arr::get($frame, 'middleware', [])
            );

            // Could be a route-model binding error, ValidatesWhenResolved
            // exception, redirect, etc.
            if (!is_null($response)) {
                return $response;
            }
        }

        // Return the last response from the stack.
        return $response ?? null;
    }

    public function addUserMethodsToStack($stack): array
    {
        $restacked = [];

        foreach ($stack as $frame) {
            $method = $frame['handler'];

            if ($method === 'automount') {
                $method = 'mount';
            }

            if (is_string($method) && method_exists($this->page, 'before' . ucfirst($method))) {
                $restacked[] = [
                    'handler' => 'before' . ucfirst($method),
                ];
            }

            $restacked[] = $frame;

            if (is_string($method) && method_exists($this->page, 'after' . ucfirst($method))) {
                $restacked[] = [
                    'handler' => 'after' . ucfirst($method),
                ];
            }
        }

        return $restacked;
    }

    public function runSyntheticAction($method, $middleware = [])
    {
        /** @var Route $synthetic */
        $synthetic = tap(clone $this->base->route(), function (Route $synthetic) use ($method) {
            // Copy the params over.
            $synthetic->parameters = $this->base->route()->parameters;

            // Set it to run the method on the FusionPage instead of the `handle`
            // method in this controller, which is where it's currently bound.
            $synthetic->flushController();
            $synthetic->action = [];

            if (is_string($method)) {
                $synthetic->uses([get_class($this->page), $method]);
            } else {
                $synthetic->uses($method);
            }
        });

        // Stash the real resolver for restoration further down.
        $original = $this->base->getRouteResolver();

        // Force it to resolve to our fake route.
        $this->base->setRouteResolver(fn() => $synthetic);

        if (is_string($method)) {
            $middleware = $this->makeMiddleware(new ReflectionMethod($this->page, $method), $middleware);
        }

        return (new Pipeline($this->app))
            ->send($this->base)
            ->through($middleware)
            ->then(function () use ($synthetic, $original) {
                $this->base->setRouteResolver($original);

                return $synthetic->run();
            });
    }

    public function makeMiddleware(ReflectionMethod $method, ?array $existing = []): array
    {
        return collect($method->getAttributes(Middleware::class))
            // Check for any middleware that might be annotated on the action.
            ->flatMap(fn(ReflectionAttribute $attribute) => $attribute->newInstance()->middleware)
            // Put the Fusion-defined middleware first, before the user-defined set.
            ->unshift(...$existing)
            // This will make sure they're all unique.
            ->pipe(fn($middleware) => app(Router::class)->resolveMiddleware($middleware->all()));
    }
}
