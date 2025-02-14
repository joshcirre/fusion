<?php

/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace Fusion\Reflection;

use ReflectionClass as BaseReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

class ReflectionClass extends BaseReflectionClass
{
    public function collectMethods($filter = null): ReflectionCollection
    {
        return ReflectionCollection::make($this->getMethods($filter));
    }

    public function collectProperties($filter = null): ReflectionCollection
    {
        return ReflectionCollection::make($this->getProperties($filter));
    }

    public function withPublicMethods(?callable $cb = null): ReflectionCollection
    {
        return $this->collectMethods()
            ->filterAllModifiers([
                ReflectionMethod::IS_PUBLIC
            ])
            ->rejectAnyModifiers([
                ReflectionMethod::IS_ABSTRACT,
                ReflectionMethod::IS_STATIC
            ])
            // If `cb` is null then we use `value` as a noop.
            ->cleanMap(fn(ReflectionMethod $method) => ($cb ?? value(...))($method));
    }

    public function withPublicProperties(?callable $cb = null): ReflectionCollection
    {
        return $this->collectProperties()
            ->filterAllModifiers([
                ReflectionProperty::IS_PUBLIC,
            ])
            ->rejectAnyModifiers([
                ReflectionProperty::IS_STATIC,
            ])
            // If `cb` is null then we use `value` as a noop.
            ->cleanMap(fn(ReflectionMethod $method) => ($cb ?? value(...))($method));
    }
}
