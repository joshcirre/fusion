<?php

/**
 * @author Aaron Francis <aarondfrancis@gmail.com>
 * @link https://aaronfrancis.com
 * @link https://twitter.com/aarondfrancis
 */

namespace Fusion\Tests\Unit;

use Fusion\Providers\FusionServiceProvider;
use Fusion\Tests\Support\FusionTestServiceProvider;
use Orchestra\Testbench\TestCase;

abstract class Base extends TestCase
{
    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function getEnvironmentSetup($app) {}

    protected function getPackageProviders($app)
    {
        return [
            FusionServiceProvider::class,
            FusionTestServiceProvider::class,
        ];
    }
}
