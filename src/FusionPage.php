<?php

/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace Fusion;

use Fusion\Attributes\ServerOnly;
use Fusion\Concerns\BootsTraits;
use Fusion\Concerns\ComputesState;
use Fusion\Concerns\HandlesExposedActions;
use Fusion\Concerns\InteractsWithQueryStrings;
use Fusion\Reflection\FusionPageReflection;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\ControllerDispatcher;
use Inertia\Inertia;
use Inertia\Response;
use ReflectionParameter;
use ReflectionProperty;

class FusionPage
{
    use BootsTraits, ComputesState, HandlesExposedActions, InteractsWithQueryStrings;

    #[ServerOnly]
    public FusionPageReflection $reflector;

    public function __construct()
    {
        $this->reflector = new FusionPageReflection($this);

        $this->boot();
        $this->bootTraits();
    }

    public function boot()
    {
        //
    }

    public function isProcedural(): bool
    {
        return property_exists($this, 'isProcedural');
    }

    public function initializePageState(): void
    {
        foreach ($this->reflector->propertiesInitFromState() as $property) {
            /** @var ReflectionProperty $property */
            if (Fusion::request()->state->has($property->name)) {
                $this->{$property->name} = Fusion::request()->state->get($property->name);
            }
        }
    }

    public function handlePageRequest(): JsonResponse|Response
    {
        Fusion::response()->mergeState($this->state());

        if (Fusion::request()->isFusionHmr() || Fusion::request()->isFusionAction()) {
            return response()->json(Fusion::response()->forTransport());
        }

        return Inertia::render(
            Fusion::request()->component(), Fusion::response()->forTransport()
        );
    }

    public function fusionSync(): JsonResponse
    {
        // Pretty simple, just send all the state back out as JSON.
        return $this->handlePageRequest();
    }

    /**
     * This is a bit of magic from Laravel. Whenever a controller action is about
     * to be run, you can intercept the call if there's a callAction method.
     * We intercept it so we can muck about with the parameters a bit.
     *
     * @see ControllerDispatcher::dispatch()
     */
    public function callAction($method, $parameters)
    {
        // Automount has no signature and requires named params, so we're done.
        if ($method === 'automount') {
            return $this->automount($parameters);
        }

        $dependencies = $this->reflector->getMethod($method)->getParameters();

        $named = [];
        $positional = [];

        // Split the given parameters into named and positional so
        // that we can load them into their correct positions.
        foreach ($parameters as $key => $parameter) {
            if (is_int($key)) {
                $positional[] = $parameter;
            } else {
                $named[$key] = $parameter;
            }
        }

        $dependencies = collect($dependencies)
            ->mapWithKeys(function (ReflectionParameter $dependency) use ($named, &$positional) {
                $name = $dependency->getName();

                if (array_key_exists($name, $named)) {
                    $value = $named[$name];
                } else {
                    // Positional arguments just go in order.
                    $value = count($positional) ? array_shift($positional) : null;
                }

                return [$name => $value];
            });

        return $this->$method(...$dependencies->all());
    }

    protected function automount($bound): void
    {
        foreach ($bound as $key => $value) {
            if (property_exists($this, $key)) {
                $this->{$key} = $value;
            }
        }
    }
}
