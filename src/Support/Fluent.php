<?php

/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace Fusion\Support;

class Fluent extends \Illuminate\Support\Fluent
{
    public function merge($key, array $value): Fluent
    {
        $array = $this->get($key, []);

        $array = [...$array, ...$value];

        return $this->set($key, $array);
    }

    public function push($key, $value): Fluent
    {
        $array = $this->get($key, []);

        $array[] = $value;

        return $this->set($key, $array);
    }
}
