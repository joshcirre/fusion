<?php

/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace Fusion\Support;

use Closure;
use Fusion\Routing\PendingBind;

class PendingProp
{
    public string $name;

    public mixed $default = null;

    public ?Closure $valueResolver = null;

    public ?string $fromRoute = null;

    public ?string $queryStringName = null;

    public ?PendingBind $binding = null;

    public bool $readonly = false;

    public function __construct(string $name, $default = null)
    {
        $this->name = $name;
        $this->default = $default;
    }

    public function setValueResolver(Closure $closure): static
    {
        $this->valueResolver = $closure;

        return $this;
    }

    public function fromRoute(
        ?string $param = null,
        ?string $class = null,
        ?string $using = null,
        bool $withTrashed = false
    ): static {
        $this->fromRoute = $param ?? $this->name;
        $this->readonly = true;

        $this->binding = (new PendingBind)->to($class)->using($using)->withTrashed($withTrashed);

        return $this;
    }

    public function syncQueryString(?string $as = null): static
    {
        $this->queryStringName = $as ?? $this->name;

        return $this;
    }

    public function readonly(bool $readonly = true): static
    {
        $this->readonly = $readonly;

        return $this;
    }

    public function value(): mixed
    {
        return call_user_func($this->valueResolver, $this);
    }
}
