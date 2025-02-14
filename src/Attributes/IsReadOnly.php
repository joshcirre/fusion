<?php

/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace Fusion\Attributes;

use Attribute;

#[Attribute]
readonly class IsReadOnly
{
    public function __construct() {}
}
