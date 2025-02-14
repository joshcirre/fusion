<?php

/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace Fusion\Http\Response\Actions;

class SyncQueryString extends ResponseAction
{
    public string $handler = 'syncQueryString';

    public function __construct(public string $property, public string $query)
    {
        //
    }
}
