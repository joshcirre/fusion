<?php

/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace Fusion\Reflection;

use App\Http\Actions\Computed;
use Fusion\Attributes\IsReadOnly;
use Fusion\Attributes\ServerOnly;
use Fusion\Attributes\SyncQueryString;
use Fusion\Fusion;
use Fusion\FusionPage;
use Illuminate\Support\Str;
use ReflectionMethod;
use ReflectionProperty;

class FusionPageReflection extends ReflectionClass
{
    public FusionPage $instance;

    public function __construct(FusionPage $instance)
    {
        $this->instance = $instance;

        parent::__construct($instance);
    }

    public function pageLevelRouteBindings(): array
    {
        return $this->hasMethod('mount') ? $this->bindingsForMountFunction() : $this->bindingsForAutomountFunction();
    }

    public function bindingsForAction($name): array
    {
        $bindings = [];

        $reflection = $this->getMethod($name);

        foreach ($reflection->getParameters() as $parameter) {
            $class = $parameter->getType()?->getName();
            $bindings[$parameter->getName()] = [
                // @TODO attributes to influence key and withTrashed?
                'class' => $class
            ];
        }

        return $bindings;
    }

    protected function bindingsForMountFunction(): array
    {
        $method = $this->getMethod('mount');

        $bindings = [];
        foreach ($method->getParameters() as $param) {
            // @TODO make this a DTO
            // 'podcast' => [
            //     'class' => \App\Models\Podcast::class,
            //     'key' => 'slug',
            //     'withTrashed' => true,
            // ]

            $class = $param->getType()?->getName();
            $bindings[$param->getName()] = [
                // @TODO attributes to influence key and withTrashed?
                'class' => $class
            ];
        }

        return $bindings;
    }

    protected function bindingsForAutomountFunction(): array
    {
        $bindings = [];

        $parameters = $this->getProperties(ReflectionProperty::IS_PUBLIC | ReflectionProperty::IS_PROTECTED);

        foreach ($parameters as $parameter) {
            if (!Fusion::request()->base->route()->hasParameter($parameter->getName())) {
                continue;
            }

            $class = $parameter->getType()?->getName();
            $bindings[$parameter->getName()] = [
                // @TODO attributes to influence key and withTrashed?
                'class' => $class
            ];
        }

        return $bindings;
    }

    public function exposedActionMethods(): ReflectionCollection
    {
        return $this->collectMethods()
            ->filterAnyModifiers([
                ReflectionProperty::IS_PUBLIC,
            ])
            ->rejectAnyAnnotations([
                ServerOnly::class,
            ])
            ->filter(function (ReflectionMethod $method) {
                // These are public, but written by Fusion, so we exclude them.
                if (in_array($method->name, ['mount', 'runProceduralCode'])) {
                    return false;
                }

                // Any public methods written by the developer.
                if ($method->class !== FusionPage::class) {
                    return true;
                }

                // Or any methods prefixed with `fusion`, as these are helpers
                // that end up as `fusion.[xxx]` on the frontend.
                return Str::startsWith($method->name, 'fusion');
            })
            ->values();
    }

    public function computedPropertyMethods(): ReflectionCollection
    {
        return $this->collectMethods()
            ->filterAllModifiers([
                ReflectionMethod::IS_PUBLIC,
            ])
            ->pipe(function (ReflectionCollection $collection) {
                return $collection->filter(function (ReflectionMethod $method) {
                    // It matches our getFooProp convention or is explicitly tagged.
                    return
                        Str::isMatch('/^get(.*)Prop$/', $method->getName())
                        || Reflector::isAnnotatedByAny($method, Computed::class);
                });
            });
    }

    public function propertiesForState(): ReflectionCollection
    {
        return $this->collectProperties()
            ->filterAllModifiers(ReflectionProperty::IS_PUBLIC)
            ->rejectAnyModifiers(ReflectionProperty::IS_STATIC)
            ->rejectAnyAnnotations(ServerOnly::class);
    }

    public function propertiesInitFromState(): ReflectionCollection
    {
        return $this->propertiesForState()
            ->rejectAnyAnnotations(IsReadOnly::class);
    }

    public function propertiesInitFromQueryString(): ReflectionCollection
    {
        return $this->collectProperties()
            ->filterAnyAnnotations([
                SyncQueryString::class
            ])
            ->filterAnyModifiers([
                ReflectionProperty::IS_PUBLIC,
                ReflectionProperty::IS_PROTECTED,
            ])
            ->rejectAnyModifiers([
                ReflectionProperty::IS_STATIC,
                ReflectionProperty::IS_READONLY,
            ]);
    }
}
