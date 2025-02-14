<?php

/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace Fusion\Reflection;

/*
 * What a fun name!
 */

class ReflectionCollection extends \Illuminate\Support\Collection
{
    public function cleanMap(?callable $cb = null): static
    {
        return $this
            ->when(!is_null($cb), fn($collection) => $collection->map($cb))
            ->filter()
            ->values();
    }

    public function filterAnyAnnotations(string|array $classes = []): static
    {
        return $this->filter(function ($reflection) use ($classes) {
            return Reflector::isAnnotatedByAny($reflection, $classes);
        });
    }

    public function rejectAnyAnnotations(string|array $classes = []): static
    {
        return $this->reject(function ($reflection) use ($classes) {
            return Reflector::isAnnotatedByAny($reflection, $classes);
        });
    }

    public function filterAllModifiers(int|array $mask): static
    {
        return $this->filter(function ($reflection) use ($mask) {
            return $this->allModifierBitsOn($reflection, $mask);
        });
    }

    public function filterAnyModifiers(int|array $mask): static
    {
        return $this->filter(function ($reflection) use ($mask) {
            return $this->anyModifierBitsOn($reflection, $mask);
        });
    }

    public function rejectAllModifiers(int|array $mask): static
    {
        return $this->reject(function ($reflection) use ($mask) {
            return $this->allModifierBitsOn($reflection, $mask);
        });
    }

    public function rejectAnyModifiers(int|array $mask): static
    {
        return $this->reject(function ($reflection) use ($mask) {
            return $this->anyModifierBitsOn($reflection, $mask);
        });
    }

    public function filterMany(array $filters): static
    {
        return $this->filter(function ($value) use ($filters) {
            foreach ($filters as $filter) {
                if (!$filter($value)) {
                    return false;
                }
            }

            return true;
        });
    }

    public function rejectMany(array $rejectors): static
    {
        return $this->reject(function ($value) use ($rejectors) {
            foreach ($rejectors as $rejector) {
                if ($rejector($value)) {
                    return true;
                }
            }

            return false;
        });
    }

    protected function allModifierBitsOn($reflection, int|array $mask): bool
    {
        $mask = is_array($mask) ? array_sum($mask) : $mask;

        return (Reflector::getModifiers($reflection) & $mask) === $mask;
    }

    protected function anyModifierBitsOn($reflection, int|array $mask): bool
    {
        $mask = is_array($mask) ? array_sum($mask) : $mask;

        return (Reflector::getModifiers($reflection) & $mask) !== 0;
    }
}
