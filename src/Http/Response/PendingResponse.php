<?php

/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace Fusion\Http\Response;

use Exception;
use Fusion\Fusion;
use Fusion\Http\Response\Actions\ApplyServerState;
use Fusion\Http\Response\Actions\Log;
use Fusion\Http\Response\Actions\LogStack;
use Fusion\Http\Response\Actions\ResponseAction;
use Fusion\Support\Fluent;

class PendingResponse
{
    protected Fluent $bag;

    public static array $stack = [
        // [00, LogStack::class],
        // [20, SignFunctionParams::class],
        // [20, Rehydrate::class],
        [40, ApplyServerState::class],
        // [50, Log::class],
    ];

    public function __construct()
    {
        $this->bag = new Fluent([
            'meta' => [
                // Fusion metadata
            ],
            'state' => [
                // State that will be applied to the frontend. Note that Fusion
                // handles the crucial state itself, so developers probably
                // won't need to add anything here.
            ],
            'actions' => [
                // A stack of actions to be run on the frontend after receiving a
                // Fusion response. Each one is tied to a JavaScript handler.
            ],
        ]);

        foreach (static::$stack as [$priority, $action]) {
            $this->addAction($action, $priority);
        }
    }

    public function hasPendingState(): bool
    {
        return !empty($this->bag->get('state', []));
    }

    public function mergeState(array $state): static
    {
        $this->bag->merge('state', $state);

        return $this;
    }

    /**
     * @throws Exception
     */
    public function addAction(string|ResponseAction $action, $priority = 50): static
    {
        if (is_string($action)) {
            if (is_a($action, ResponseAction::class, true)) {
                $action = new $action;
            } else {
                throw new Exception('Action must be an instance of ResponseAction');
            }
        }

        $action->priority = $priority;

        $this->bag->push('actions', $action);

        return $this;
    }

    public function forTransport(): array
    {
        return [
            // Everything gets nested under the `fusion`
            // key on the way in and the way out.
            'fusion' => $this->bag->toArray()
        ];
    }
}
