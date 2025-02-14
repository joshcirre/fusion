<?php

/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace Fusion\Concerns;

use App\Http\Actions\Computed;
use Fusion\Casting\JavaScriptVariable;
use Fusion\FusionPage;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use ReflectionMethod;
use ReflectionProperty;

/**
 * @mixin FusionPage
 */
trait ComputesState
{
    public function state(): array
    {
        // @TODO casting, cleaning up prop names

        return collect()
            ->merge($this->getStateFromPublicProperties())
            ->merge($this->getStateFromComputedMethods())
            ->when($this->isProcedural(), function ($collection) {
                return $collection->merge($this->props);
            })
            ->mapWithKeys(fn($value, $key) => [
                $this->formatStateKey($key) => $value
            ])
            ->all();
    }

    protected function getStateFromComputedMethods(): Collection
    {
        return $this->reflector->computedPropertyMethods()
            ->mapWithKeys(function (ReflectionMethod $method) {
                return [
                    $method->getName() => $method->invoke($this)
                ];
            })
            ->toBase();

    }

    protected function getStateFromPublicProperties()
    {
        return $this->reflector->propertiesForState()
            ->keyBy(fn(ReflectionProperty $p) => $p->getName())
            ->map(function (ReflectionProperty $property) {
                $value = $this->getValueFromProperty($property);

                return $value;

                return JavaScriptVariable::makeWithValue($property, $value)->toTransportable();
            })
            ->toArray();
    }

    protected function formatStateKey(string $key): string
    {
        // Computed props from methods
        $key = Str::match('/^get(.*)Prop$/', $key) ?: $key;

        return Str::camel(lcfirst($key));
    }

    protected function getValueFromProperty(ReflectionProperty $property)
    {
        return $property->isInitialized($this) ? $this->{$property->getName()} : null;
    }
}
