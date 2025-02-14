<?php

/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace Fusion\Casting\Casters;

use DateTime;
use DateTimeInterface;
use Fusion\Casting\CasterInterface;
use Fusion\Casting\Transportable;
use Fusion\Reflection\ReflectionClass;
use ReflectionNamedType;

class DateTimeCaster implements CasterInterface
{
    public function supportsType(ReflectionNamedType $type): bool
    {
        return is_a($type->getName(), DateTimeInterface::class, true);
    }

    public function toTransport(ReflectionNamedType $type, mixed $value): Transportable
    {
        /** @var DateTimeInterface $value */
        return Transportable::make($value?->getTimestamp())->withMeta([
            'class' => is_null($value) ? $type->getName() : get_class($value),
        ]);
    }

    public function fromTransport(array $transportable): mixed
    {
        // Create a reinstantiableAs instance from the integer timestamp
    }

    protected function reinstantiableAs($class): mixed
    {
        if (!class_exists($class)) {
            return DateTime::class;
        }

        if ((new ReflectionClass($class))->isInstantiable()) {
            return $class;
        }

        // At this point, the class is either an abstract or an interface.
        // We'll see if there's a instantiable class bound in the
        // container, otherwise we're out of options.
        if (app()->bound($class)) {
            return get_class(app()->make($class));
        }

        return DateTime::class;
    }
}
