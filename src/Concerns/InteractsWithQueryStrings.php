<?php

/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace Fusion\Concerns;

use Exception;
use Fusion\Attributes;
use Fusion\Fusion;
use Fusion\FusionPage;
use Fusion\Http\Response\Actions;
use Illuminate\Http\Request;
use ReflectionProperty;

/**
 * @mixin FusionPage
 */
trait InteractsWithQueryStrings
{
    public function initializeFromQueryString(Request $request): void
    {
        $this->reflector->propertiesInitFromQueryString()
            ->each(function (ReflectionProperty $property) {
                $name = $property->getName();

                $attr = $property->getAttributes(Attributes\SyncQueryString::class);
                $attr = head($attr)->newInstance();

                // It's possible to name the query string one thing ($attr->as)
                // and the property something else ($property->getName().)
                $q = $attr->as ?? $name;

                $this->addQueryStringSyncAction($name, $q);

                if (!$this->hasQueryStringValue($name, $q)) {
                    return;
                }

                $value = $this->valueFromQueryString($name, $q);

                $this->{$name} = $value;
            });
    }

    protected function addQueryStringSyncAction($name, $q): void
    {
        Fusion::response()->addAction(
            new Actions\SyncQueryString($name, $q)
        );
    }

    protected function hasQueryStringValue($name, $q): mixed
    {
        return request()->isFusionAction() && request()->json()->has($name) || request()->query->has($q);
    }

    protected function valueFromQueryString($name, $q): mixed
    {
        // In a scenario where the querystring is "?search=foo" and a Fusion action
        // comes in with some data that says {search: bar} we need to defer to the
        // JSON data, otherwise the search property from the action will be inert.
        if (Fusion::request()->isFusionAction() && Fusion::request()->state->has($name)) {
            return Fusion::request()->state->get($name);
        } elseif (request()->query->has($q)) {
            return Fusion::request()->base->query($q);
        }

        throw new Exception('No value available. Guard with `hasQueryStringValue` before calling.');
    }
}
