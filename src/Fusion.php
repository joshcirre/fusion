<?php

/**
 * @author Aaron Francis <aarondfrancis@gmail.com>
 * @link https://aaronfrancis.com
 * @link https://twitter.com/aarondfrancis
 */

namespace Fusion;

use Illuminate\Support\Facades\Facade;

class Fusion extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return FusionManager::class;
    }
}
