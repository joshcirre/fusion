<?php

/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace Fusion\Http\Middleware;

use Fusion\Fusion;
use Illuminate\Http\Request;

class RouteBindingForAction extends RouteBinding
{
    public function getBindings(): array
    {
        $route = Fusion::request()->base->route();
        $page = Fusion::request()->page;

        $bindings = $page->reflector->bindingsForAction($route->getActionMethod());

        // Reset the parameters, as these are likely bound to the page request
        // and we don't want them to collide with the action request.
        $route->parameters = [];

        // We manually set the parameters because we don't have them defined in the URL.
        // Typically these are parsed via regex. We check to see what parameters the
        // function is requesting and, if they're present, add them to the route.
        foreach ($bindings as $name => $binding) {
            if (Fusion::request()->args->has($name)) {
                $route->setParameter($name, Fusion::request()->args->get($name));
            }
        }

        return $bindings;
    }
}
