<?php

/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace Fusion\Http\Middleware;

use Fusion\Fusion;

class RouteBindingForPage extends RouteBinding
{
    public function getBindings(): array
    {
        return Fusion::request()->page->reflector->pageLevelRouteBindings();
    }
}
