<?php

/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace Fusion\Concerns;

use Fusion\FusionPage;
use Fusion\Reflection\ReflectionCollection;
use Fusion\Reflection\Reflector;
use Illuminate\Contracts\Routing\UrlRoutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use ReflectionMethod;
use ReflectionParameter;

/**
 * @mixin FusionPage
 */
trait HandlesExposedActions
{
    public function actions(): array
    {
        return $this->reflector->exposedActionMethods()
            ->keyBy(fn(ReflectionMethod $m) => $m->getName())
            ->map(function (ReflectionMethod $method, $name) {
                return [
                    'name' => $name,
                    'integrity' => $this->computeActionIntegrity($method),
                    'signature' => $this->calculateJavaScriptSignature($method),
                ];
            })
            ->all();
    }

    protected function calculateJavaScriptSignature(ReflectionMethod $method)
    {
        return ReflectionCollection::make($method->getParameters())
            ->map(function (ReflectionParameter $parameter) {
                if (Reflector::isParameterSubclassOf($parameter, UrlRoutable::class)) {
                    $type = $this->getTypeFromUrlRoutableParameter($parameter);
                } elseif (Reflector::isParameterBackedEnumWithStringBackingType($parameter)) {
                    $type = 'string';
                } else {
                    $type = 'mixed';
                }

                return [
                    'name' => $parameter->getName(),
                    'type' => $type,
                    'nullable' => $parameter->allowsNull(),
                ];
            });
    }

    protected function getTypeFromUrlRoutableParameter(ReflectionParameter $parameter): string
    {
        // @TODO
        // $route->allowsTrashedBindings()
        // $route->preventsScopedBindings()
        // $route->enforcesScopedBindings()

        /** @var UrlRoutable $instance */
        $instance = app()->make(Reflector::getParameterClassName($parameter));

        if (is_a($instance, Model::class)) {
            return $instance->getKeyType();
        }

        return 'mixed';
    }

    protected function computeActionIntegrity(ReflectionMethod $method): string
    {
        // We don't care about this in prod. This is just for knowing when a function
        // changed so much that we need to fully reload the page in development.
        if (app()->environment('production')) {
            return '';
        }

        // This is a description of where the body of the function is and how many
        // lines it is. We don't care if the body changes, just the signature.
        $exported = Str::replaceMatches('/^\s*@@.*$/m', '', (string) $method);

        return 'func_' . md5($exported);
    }
}
