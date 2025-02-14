<?php

/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace Fusion\Casting;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

class Transportable implements Arrayable, JsonSerializable
{
    protected mixed $value;

    protected array $meta = [];

    public static function unknown(mixed $value): static
    {
        $transportable = new static($value);
        $transportable->meta = [
            'type' => 'raw'
        ];

        return $transportable;
    }

    public static function make(mixed $value): static
    {
        return new static($value);
    }

    public function __construct(mixed $value)
    {
        $this->value = $value;
    }

    public function withMeta($meta): static
    {
        $this->meta = [
            ...$this->meta,
            ...$meta
        ];

        return $this;
    }

    public function toArray(): array
    {
        return [
            'value' => $this->value,
            'meta' => empty($this->meta) ? [] : [
                // Magic key that we look for on the frontend. to tell if
                // this object needs to be unwrapped into a real value.
                'isFusion' => true,
                ...$this->meta,

                // Since functions rely on the metadata being there and
                // being correct, we need to sign it. Especially if
                // we (or the end developer) includes any FQCNs.
                'checksum' => $this->signature(),
            ],
        ];
    }

    protected function signature(): string
    {
        $sorted = $this->meta;
        ksort($sorted);

        return hash('sha256', json_encode($sorted) . config('app.key'));
    }

    public function jsonSerialize(): string
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR);
    }
}
