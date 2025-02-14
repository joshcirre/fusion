<?php

/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace Fusion\Reflection;

use Illuminate\Support\Arr;
use ReflectionMethod;
use ReflectionProperty;

class Reflector extends \Illuminate\Support\Reflector
{
    public static function getModifiers($reflection): mixed
    {
        return match (get_class($reflection)) {
            ReflectionMethod::class, ReflectionProperty::class => $reflection->getModifiers(),
        };
    }

    public static function isAnnotatedByAny($reflection, string|array $classes): bool
    {
        $classes = Arr::wrap($classes);

        // If it doesn't have a getAttributes method, it definitely doesn't have any annotations.
        $attributes = is_callable([$reflection, 'getAttributes']) ? $reflection->getAttributes() : [];

        if (!$attributes) {
            return false;
        }

        foreach ($classes as $class) {
            if ($reflection->getAttributes($class)) {
                return true;
            }
        }

        return false;
    }
}
