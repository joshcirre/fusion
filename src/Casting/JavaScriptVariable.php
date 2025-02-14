<?php

namespace Fusion\Casting;

use Exception;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionProperty;
use ReflectionUnionType;

class JavaScriptVariable
{
    protected bool $valueSet = false;

    protected mixed $value;

    protected ?CasterInterface $caster = null;

    protected ReflectionParameter|ReflectionProperty $reflection;

    public static function make($reflection): static
    {
        return new static($reflection);
    }

    public static function makeWithValue($reflection, $value): static
    {
        return (new static($reflection))->withValue($value);
    }

    public function __construct(ReflectionParameter|ReflectionProperty $reflection)
    {
        $this->reflection = $reflection;
    }

    public function withValue(mixed $value): JavaScriptVariable
    {
        $this->valueSet = true;
        $this->value = $value;

        return $this;
    }

    public function getCasterForCasting(): ?CasterInterface
    {
        // Attempt to find a matching caster based on the reflection type
        $type = $this->reflection->getType();

        return CasterRegistry::getCasterForType($type);

        //        if ($type instanceof ReflectionUnionType) {
        //            // If it's a union type, try each named type until we find a caster
        //            foreach ($type->getTypes() as $namedType) {
        //                $tempCaster = CasterRegistry::getCasterForType($namedType);
        //                if ($tempCaster !== null) {
        //                    $this->caster = $tempCaster;
        //                    break;
        //                }
        //            }
        //        } elseif ($type instanceof ReflectionNamedType) {
        //            // Single named type
        //            $this->caster = CasterRegistry::getCasterForType($type);
        //        }
    }

    /**
     * Convert the stored value into a transportable structure for JS.
     */
    public function toTransportable(): Transportable
    {
        if (!$this->valueSet) {
            throw new Exception('Cannot create a transportable JavaScript variable without a value.');
        }

        if (!$caster = $this->getCasterForCasting()) {
            return Transportable::unknown($this->value);
        }

        return $caster
            ->toTransport($this->reflection->getType(), $this->value)
            ->withMeta([
                'caster' => get_class($caster),
            ]);
    }

    /**
     * Helper for reconstructing a PHP value from an incoming transportable structure.
     * Example usage: $phpValue = JavaScriptVariable::fromTransportable($reflection, $_POST['myVar']);
     */
    public static function fromTransportable(
        ReflectionParameter|ReflectionProperty $reflection,
        array $transportable
    ): mixed {
        $reflectionType = $reflection->getType();

        if ($reflectionType instanceof ReflectionUnionType) {
            foreach ($reflectionType->getTypes() as $namedType) {
                $caster = CasterRegistry::getCasterForType($namedType);
                if ($caster !== null) {
                    return $caster->fromTransport($transportable);
                }
            }

            // Fallback if no caster matches
            return $transportable['value'] ?? null;

        } elseif ($reflectionType instanceof ReflectionNamedType) {
            $caster = CasterRegistry::getCasterForType($reflectionType);
            if ($caster) {
                return $caster->fromTransport($transportable);
            }
        }

        // If no caster was found, just return the raw 'value'
        return $transportable['value'] ?? null;
    }
}
