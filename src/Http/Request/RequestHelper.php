<?php

/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace Fusion\Http\Request;

use Fusion\FusionPage;
use Fusion\Http\Response\PendingResponse;
use Fusion\Support\Fluent;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RequestHelper
{
    use RunsSyntheticActions;

    public Fluent $args;

    public Fluent $state;

    public Fluent $meta;

    public FusionPage $page;

    protected bool $hasBeenInit = false;

    public function __construct(public Application $app, public Request $base)
    {
        //
    }

    public function init(): void
    {
        if ($this->hasBeenInit) {
            return;
        }

        $this->hasBeenInit = true;

        $this->processVariadics();
        $this->modifyInboundJson();
        $this->instantiatePendingResponse();
        $this->setHeaders();
        $this->forgetStashedData();

        // This should always come last, after all setup is done.
        $this->instantiateFusionPage();
    }

    public function isFusion(): bool
    {
        return $this->base->isFusion();
    }

    public function isFusionPage(): bool
    {
        return $this->base->isFusionPage();
    }

    public function isFusionHmr(): bool
    {
        return $this->base->isFusionHmr();
    }

    public function isFusionAction(): bool
    {
        return $this->base->isFusionAction();
    }

    public function component()
    {
        return $this->base->route()->defaults['__component'];
    }

    public function instantiateFusionPage(): void
    {
        // This is where we actually get the FusionPage class. It should be impossible
        // to change the param unless the developer defines a __class param in their
        // route. It's still safer to pull from the defaults, so that's what we do.
        $class = $this->base->route()->defaults['__class'];

        // When pages do not have a PHP block they don't get a generated PHP class,
        // so we just instantiate the base FusionPage class.
        if (is_null($class)) {
            $class = FusionPage::class;
        }

        // get_class() always strips the initial backslash so, to make sure
        // we can destroy it properly, we do the same when registering.
        $class = Str::chopStart($class, '\\');

        $this->app->forgetInstance($class);
        $this->app->singleton($class);

        $this->page = app($class);
    }

    protected function processVariadics(): void
    {
        $route = $this->base->route();

        // We stashed a bit of info in the defaults to denote which parameter
        // is variadic. Just like we do with the classes and components.
        if (!isset($route->defaults['__variadic'])) {
            return;
        }

        // This is the name of the variadic param.
        $variadic = $route->defaults['__variadic'];

        if (!$route->hasParameter($variadic)) {
            return;
        }

        $value = $route->parameter($variadic);

        // Overwrite the string version with an exploded version.
        $route->setParameter($variadic, is_string($value) ? explode('/', $value) : $value);
    }

    protected function modifyInboundJson(): void
    {
        $this->args = new Fluent($this->base->json('fusion.args') ?? []);
        $this->meta = new Fluent($this->base->json('fusion.meta') ?? []);
        $this->state = new Fluent($this->base->json('fusion.state') ?? []);

        // Pull the 'fusion' key out of the JSON bag so that the FusionPage
        // method only sees input relevant to the actual function, not
        // all the meta used to find the class, method, etc.
        $this->base->json()->remove('fusion');
    }

    protected function instantiatePendingResponse(): void
    {
        $this->app->forgetInstance(PendingResponse::class);
        $this->app->singleton(PendingResponse::class);
    }

    protected function setHeaders(): void
    {
        $this->base->isFusion(set: true);

        // This is set by the frontend. So if it's there we're done.
        if ($this->base->isFusionAction()) {
            return;
        }

        $this->base->isFusionPage(set: true);
    }

    protected function forgetStashedData(): void
    {
        // We set a default when we register the route, as a way to link the route to
        // the page. Since __class the parameter is always blank, it gets set to
        // the default. We remove it so that it doesn't get relied on at all.
        $this->base->route()->forgetParameter('__class');
        $this->base->route()->forgetParameter('__component');
        $this->base->route()->forgetParameter('__variadic');
    }
}
