<?php

/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace Fusion\Casting;

use Fusion\Casting\Casters\BuiltinScalarCaster;
use Fusion\Casting\Casters\DateTimeCaster;
use InvalidArgumentException;
use ReflectionNamedType;

class CasterRegistry
{
    /**
     * @var CasterInterface[]
     */
    private static array $casters = [
        BuiltinScalarCaster::class,
        DateTimeCaster::class,
    ];

    public static function registerCaster(string $caster): void
    {
        if (!is_a($caster, CasterInterface::class, true)) {
            throw new InvalidArgumentException("Caster [{$caster}] does not implement CasterInterface.");
        }

        self::$casters[] = $caster;
    }

    /**
     * Return the first caster that supports the given ReflectionNamedType.
     */
    public static function getCasterForType(ReflectionNamedType $type): ?CasterInterface
    {
        foreach (self::$casters as $caster) {
            $caster = app($caster);

            if ($caster->supportsType($type)) {
                return $caster;
            }
        }

        return null;
    }
}
