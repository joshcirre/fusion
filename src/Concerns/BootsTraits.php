<?php

/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace Fusion\Concerns;

trait BootsTraits
{
    public function bootTraits(): void
    {
        $class = static::class;

        $booted = [];

        foreach (class_uses_recursive($class) as $trait) {
            $method = 'boot' . class_basename($trait);

            if (method_exists($class, $method) && !in_array($method, $booted)) {
                call_user_func([$this, $method]);

                $booted[] = $method;
            }
        }
    }
}
