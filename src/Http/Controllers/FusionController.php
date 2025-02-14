<?php

/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace Fusion\Http\Controllers;

use Fusion\Fusion;
use Fusion\Http\Middleware\MergeStateIntoActionResponse;
use Fusion\Http\Middleware\RouteBindingForAction;
use Fusion\Http\Middleware\RouteBindingForPage;

class FusionController
{
    public function handle()
    {
        Fusion::request()->init();

        return Fusion::request()->runSyntheticStack([
            [
                'handler' => 'initializePageState',
            ], [
                'handler' => 'initializeFromQueryString',
            ], [
                'handler' => $this->methodForInstantiation(),
                'middleware' => [RouteBindingForPage::class],
            ],
            $this->methodForHandling()
        ]);
    }

    protected function methodForInstantiation()
    {
        if (Fusion::request()->page->isProcedural()) {
            return 'runProceduralCode';
        }

        return method_exists(Fusion::request()->page, 'mount') ? 'mount' : 'automount';
    }

    protected function methodForHandling(): array
    {
        if (Fusion::request()->isFusionPage()) {
            return [
                'handler' => 'handlePageRequest'
            ];
        }

        $requested = Fusion::request()->base->header('X-Fusion-Action-Handler');

        $allowed = Fusion::request()->page->reflector->exposedActionMethods()->pluck('name');

        if ($allowed->contains($requested)) {
            return [
                'handler' => $requested,
                'middleware' => [
                    RouteBindingForAction::class,
                    MergeStateIntoActionResponse::class
                ],
            ];
        }

        abort(404);
    }
}
