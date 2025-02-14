<?php

/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace Fusion\Attributes;

use Attribute;

#[Attribute]
readonly class SyncQueryString
{
    public function __construct(public ?string $as = null) {}
}
