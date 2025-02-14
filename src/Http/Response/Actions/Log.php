<?php

/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace Fusion\Http\Response\Actions;

class Log extends ResponseAction
{
    public string $handler = 'log';

    public function __construct(public string $message = '')
    {
        //
    }
}
