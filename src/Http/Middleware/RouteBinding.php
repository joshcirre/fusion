<?php

/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace Fusion\Http\Middleware;

use Closure;
use Fusion\Routing\SubstituteBindings;
use Illuminate\Http\Request;

abstract class RouteBinding
{
    public function handle(Request $request, Closure $next)
    {
        $bindings = $this->getBindings();

        if ($bindings) {
            $route = $request->route();
            $params = (new SubstituteBindings($bindings))->resolve($route->parameters());
            foreach ($params as $key => $param) {
                $route->setParameter($key, $param);
            }
        }

        return $next($request);
    }

    abstract public function getBindings(): array;
}
