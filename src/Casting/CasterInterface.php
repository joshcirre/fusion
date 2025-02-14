<?php

/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace Fusion\Casting;

use ReflectionNamedType;

interface CasterInterface
{
    /**
     * Determines if this caster supports casting for the given Reflection type.
     */
    public function supportsType(ReflectionNamedType $type): bool;

    /**
     * Converts the given PHP value into a transportable structure (array or primitive).
     */
    public function toTransport(ReflectionNamedType $type, mixed $value): Transportable;

    /**
     * Converts a transportable structure back into a PHP value.
     * (You may throw an exception if the structure is malformed or incompatible.)
     */
    public function fromTransport(array $transportable): mixed;
}
