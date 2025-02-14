<?php

/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace Fusion\Routing;

use Illuminate\Contracts\Routing\UrlRoutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Routing\Exceptions\BackedEnumCaseNotFoundException;
use Illuminate\Routing\Router;
use Illuminate\Support\Arr;
use InvalidArgumentException;
use UnitEnum;

class SubstituteBindings
{
    /**
     * Example structure:
     * [
     *   'podcast' => [
     *       'class' => \App\Models\Podcast::class,
     *       'key' => 'slug',
     *       'withTrashed' => true,
     *   ],
     *   'status' => [
     *       'class' => \App\Enums\PodcastStatus::class,
     *   ],
     * ]
     */
    protected array $bindings;

    public function __construct(array $bindings = [])
    {
        $this->bindings = $bindings;
    }

    public function resolve(array $parameters): array
    {
        $resolved = [];
        $router = app(Router::class);

        foreach ($parameters as $name => $value) {
            $resolved[$name] = $value;
            if (!isset($this->bindings[$name])) {
                continue;
            }

            $binding = $this->bindings[$name];
            $class = $binding['class'] ?? null;

            if ($router->hasExplicitBinding($name)) {
                $resolved[$name] = $router->performExplicitBinding($name, $value);
            } elseif (is_null($class)) {
                continue;
            } elseif (!class_exists($class)) {
                throw new InvalidArgumentException("Model class [{$class}] does not exist.");
            } elseif (is_a($class, UrlRoutable::class, true)) {
                $resolved[$name] = $this->resolveModelBinding($binding, $value);
            } elseif (enum_exists($class)) {
                $resolved[$name] = $this->resolveEnumBinding($class, $value);
            }
        }

        return $resolved;
    }

    protected function resolveModelBinding(array $binding, mixed $value): UrlRoutable
    {
        $class = $binding['class'];

        /** @var UrlRoutable $instance */
        $instance = new $class;

        $field = $binding['key'] ?? $instance->getRouteKeyName();

        $routeBindingMethod = $this->allowsTrashed($binding, $instance)
            ? 'resolveSoftDeletableRouteBinding'
            : 'resolveRouteBinding';

        // If the incoming param is an array but it's supposed to be a model, odds are
        // good that the frontend sent the entire object instead of just the route
        // key. If the route key (field) is present in the array, we'll use that.
        $value = is_array($value) ? Arr::get($value, $field, $value) : $value;

        $model = $instance->{$routeBindingMethod}($value, $field);

        if (!$model) {
            throw (new ModelNotFoundException)->setModel($class, [$value]);
        }

        return $model;
    }

    protected function allowsTrashed($binding, $instance): bool
    {
        return isset($binding['withTrashed'])
            && $binding['withTrashed']
            && in_array(SoftDeletes::class, class_uses_recursive($instance));
    }

    protected function resolveEnumBinding(string $class, $value): UnitEnum
    {
        $enum = $value instanceof $class ? $value : $class::tryFrom((string) $value);

        if (is_null($enum)) {
            throw new BackedEnumCaseNotFoundException($class, $value);
        }

        return $enum;
    }
}
