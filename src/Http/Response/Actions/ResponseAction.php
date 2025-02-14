<?php

/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace Fusion\Http\Response\Actions;

use Fusion\Reflection\ReflectionClass;
use Illuminate\Contracts\Support\Jsonable;
use JsonSerializable;
use ReflectionProperty;

class ResponseAction implements Jsonable, JsonSerializable
{
    public int $priority = 50;

    public function jsonSerialize(): mixed
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        $properties = (new ReflectionClass($this))->getProperties(ReflectionProperty::IS_PUBLIC);

        return collect($properties)
            ->mapWithKeys(function (ReflectionProperty $property) {
                return [$property->getName() => $property->getValue($this)];
            })
            ->put('_handler', static::class)
            ->toArray();
    }

    public function toJson($options = 0): false|string
    {
        return json_encode($this->jsonSerialize(), $options);
    }
}
