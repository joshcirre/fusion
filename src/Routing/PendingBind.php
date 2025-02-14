<?php

/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace Fusion\Routing;

use Closure;

class PendingBind
{
    public ?Closure $callback;

    public bool $withTrashed = false;

    public ?string $to = null;

    public ?string $using = null;

    public function __construct()
    {
        //
    }

    public function withTrashed(bool $trashed = true): static
    {
        $this->withTrashed = $trashed;

        return $this;
    }

    public function to(?string $model = null): static
    {
        $this->to = $model;

        return $this;
    }

    public function using(?string $key = null): static
    {
        $this->using = $key;

        return $this;
    }
}
