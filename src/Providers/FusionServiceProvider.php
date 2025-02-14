<?php

/**
 * @author Aaron Francis <aarondfrancis@gmail.com>
 * @link https://aaronfrancis.com
 * @link https://twitter.com/aarondfrancis
 */

namespace Fusion\Providers;

use Fusion\Console\Commands;
use Fusion\Enums\Frontend;
use Fusion\Fusion;
use Fusion\FusionManager;
use Fusion\Http\Controllers\HmrController;
use Fusion\Http\Request\RequestHelper;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config as ConfigFacade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class FusionServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(FusionManager::class);

        $this->mergeConfigFrom(__DIR__ . '/../../config/fusion.php', 'fusion');
    }

    public function boot()
    {
        $this->registerRouting();
        $this->registerMacros();
        $this->registerEvents();
        $this->registerBindings();

        if ($this->app->runningInConsole()) {
            $this->registerCommands();
            $this->registerBlade();
            $this->publishFiles();
        }
    }

    protected function registerMacros(): void
    {
        $macro = function ($header) {
            return function (?bool $set = null) use ($header) {
                /** @var Request $this */
                if (!is_null($set)) {
                    $this->headers->set($header, json_encode($set));
                }

                return $this->headers->get($header) === 'true';
            };
        };

        Request::macro('isFusion', $macro('X-Fusion-Request'));
        Request::macro('isFusionPage', $macro('X-Fusion-Page-Request'));
        Request::macro('isFusionHmr', $macro('X-Fusion-Hmr-Request'));
        Request::macro('isFusionAction', $macro('X-Fusion-Action-Request'));

        Router::macro('hasExplicitBinding', function ($name) {
            return array_key_exists($name, $this->binders);
        });

        Router::macro('performExplicitBinding', function ($key, $value) {
            return call_user_func($this->binders[$key], $value, null);
        });
    }

    protected function registerRouting(): void
    {
        if (!app()->environment('production')) {
            Route::get('/_fusion/hmr/invalidate', [HmrController::class, 'invalidate'])->name('hmr.invalidate');
        }
    }

    protected function registerCommands(): void
    {
        $this->commands([
            Commands\Install::class,
            Commands\Conform::class,
            Commands\Config::class,
            Commands\Shim::class,
            Commands\PostInstall::class,
            Commands\Mirror::class,
        ]);
    }

    protected function registerBindings(): void
    {
        $this->app->singleton(RequestHelper::class, function (Application $app) {
            $request = $app->make(Request::class);

            return new RequestHelper($app, $request);
        });

        ConfigFacade::set('database.connections.__fusion', [
            'name' => '__fusion',
            'driver' => 'sqlite',
            'database' => Fusion::storage('fusion.sqlite'),
            'foreign_key_constraints' => true,
            // Since we're super low concurrency and the entire DB can be rebuilt on command, we opt for the convenience of a single file at the expense of a higher risk of corruption on crash.
            'journal_mode' => 'OFF',
        ]);
    }

    protected function registerEvents(): void
    {
        //
    }

    protected function registerBlade(): void
    {
        $frontend = Arr::get($this->app->config['fusion'], 'frontend', Frontend::Vue);

        if ($frontend instanceof Frontend) {
            $frontend = $frontend->value;
        }

        $this->loadViewsFrom(__DIR__ . '/../Blade/' . ucfirst(Str::camel($frontend)), 'fusion');
    }

    protected function publishFiles(): void
    {
        $this->publishes([
            __DIR__ . '/../../config/fusion.php' => config_path('fusion.php'),
        ], 'fusion-config');
    }
}
