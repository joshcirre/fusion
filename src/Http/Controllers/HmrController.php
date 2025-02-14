<?php

/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace Fusion\Http\Controllers;

use Illuminate\Http\Request;

class HmrController
{
    public function __construct()
    {
        if (app()->environment('production')) {
            abort(404);
        }
    }

    public function invalidate(Request $request): void
    {
        if (!function_exists('opcache_get_status') || !function_exists('opcache_invalidate')) {
            return;
        }

        $status = opcache_get_status(false);

        if (isset($status['opcache_enabled']) && $status['opcache_enabled']) {
            if ($file = $request->query('file')) {
                opcache_invalidate($file, force: true);
            }
        }
    }
}
