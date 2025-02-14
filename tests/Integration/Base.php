<?php

/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace AaronFrancis\Duo\Tests\Integration;

use AaronFrancis\Duo\Providers\DuoServiceProvider;
use Orchestra\Testbench\TestCase;

use function Orchestra\Testbench\package_path;

abstract class Base extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [
            //            DuoServiceProvider::class,
            //            DuoTestServiceProvider::class,
        ];
    }

    protected function setUp(): void
    {
        //        $this->afterApplicationCreated(function () {
        //            touch(storage_path('logs/laravel.log'));
        //            @symlink(
        //                package_path('vendor', 'bin', 'testbench'),
        //                package_path() . '/artisan',
        //            );
        //        });

        parent::setUp();
    }
}
