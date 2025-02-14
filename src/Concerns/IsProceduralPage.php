<?php

/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace Fusion\Concerns;

use Closure;
use Fusion\Fusion;
use Fusion\FusionPage;
use Fusion\Reflection\ReflectionCollection;
use Fusion\Routing\SubstituteBindings;
use Fusion\Support\PendingProp;
use Laravel\SerializableClosure\Support\ReflectionClosure;
use ReflectionParameter;

/**
 * @mixin FusionPage
 */
trait IsProceduralPage
{
    protected bool $isProcedural = true;

    protected array $props = [
        //
    ];

    protected array $actions = [
        //
    ];

    // This is the user's code from the .vue file.
    abstract public function runProceduralCode();

    protected function syncProps($definedVariables): void
    {
        foreach ($this->props as $name => $value) {
            $this->props[$name] = $definedVariables[$name] ?? null;
        }
    }

    protected function resolveProvidedValue(PendingProp $prop)
    {
        $this->props[$prop->name] = null;

        if ($prop->fromRoute) {
            return $this->getPropValueFromRoute($prop);
        }

        if ($prop->queryStringName) {
            $this->addQueryStringSyncAction($prop->name, $prop->queryStringName);

            if ($this->hasQueryStringValue($prop->name, $prop->queryStringName)) {
                return $this->valueFromQueryString($prop->name, $prop->queryStringName);
            }
        }

        if ($prop->readonly) {
            return value($prop->default);
        }

        // State is a Fluent, so `get` calls `value` on default.
        return Fusion::request()->state->get($prop->name, $prop->default);
    }

    protected function prop($default = null, string $name = ''): PendingProp
    {
        return (new PendingProp($name, $default))
            ->setValueResolver($this->resolveProvidedValue(...));
    }

    protected function expose(...$args): void
    {
        foreach ($args as $name => $handler) {
            $this->actions[$name] = $handler;
        }
    }

    protected function mount(Closure $callback)
    {
        $reflection = new ReflectionClosure($callback);

        // Check their mount signature for arguments we can use.
        $bindings = ReflectionCollection::make($reflection->getParameters())
            ->keyBy('name')
            ->map(function (ReflectionParameter $item) {
                $type = $item->getType()?->getName();

                return [
                    'class' => class_exists($type) ? $type : null,
                ];
            })
            ->toArray();

        $parameters = Fusion::request()->base->route()->parameters();

        $resolved = (new SubstituteBindings($bindings))->resolve($parameters);

        return app()->call($callback, $resolved);
    }

    protected function getPropValueFromRoute(PendingProp $prop)
    {
        $parameters = Fusion::request()->base->route()->parameters();

        $binding = is_null($prop->binding) ? [] : [
            'class' => $prop->binding->to,
            'key' => $prop->binding->using,
            'withTrashed' => $prop->binding->withTrashed,
        ];

        $binder = new SubstituteBindings([
            $prop->fromRoute => $binding,
        ]);

        return $binder->resolve($parameters)[$prop->fromRoute];
    }
}
