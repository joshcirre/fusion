<?php

/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace Fusion\Casting\Casters;

use Fusion\Casting\CasterInterface;
use Fusion\Casting\Transportable;
use Illuminate\Support\Arr;
use ReflectionNamedType;

class BuiltinScalarCaster implements CasterInterface
{
    public function supportsType(ReflectionNamedType $type): bool
    {
        return in_array($type->getName(), [
            'bool',
            'float',
            'int',
            'null',
            'string',
            'false',
            'true',
        ]);

        // @TODO
        // 'array', 'callable', 'object', 'iterable', 'never', 'void', 'mixed'
    }

    public function toTransport(ReflectionNamedType $type, mixed $value): Transportable
    {
        return Transportable::make($value)->withMeta([
            'type' => $type->getName(),
        ]);
    }

    public function fromTransport(array $transportable): mixed
    {
        $value = Arr::get($transportable, 'value');
        $type = Arr::get($transportable, 'meta.type');

        if (is_null($value)) {
            return null;
        }

        return match ($type) {
            'bool' => (bool) $value,
            'float' => (float) $value,
            'int' => (int) $value,
            'string' => (string) $value,
            'false' => false,
            'true' => true,
        };
    }
}
