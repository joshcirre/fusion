<?php

/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace Fusion\Http\Middleware;

use Closure;
use Fusion\Fusion;
use Illuminate\Http\Request;

class MergeStateIntoActionResponse
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if (Fusion::response()->hasPendingState()) {
            return $response;
        }

        return $response;
    }
}
