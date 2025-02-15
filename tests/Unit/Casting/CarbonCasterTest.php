<?php

/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace Fusion\Tests\Unit\Casting;

use Carbon\CarbonImmutable;
use Fusion\Casting\CasterRegistry;
use Fusion\Casting\Casters\DateTimeCaster;
use Fusion\Casting\JavaScriptVariable;
use Fusion\Tests\Unit\Base;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use ReflectionFunction;

class CarbonCasterTest extends Base
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->markTestSkipped();

        Carbon::setTestNow('1989-02-14 08:43:00');

        CasterRegistry::registerCaster(DateTimeCaster::class);
    }

    protected function firstParameter($cb): \ReflectionParameter
    {
        $reflection = new ReflectionFunction($cb);

        return $reflection->getParameters()[0];
    }

    #[Test]
    public function basic_test()
    {
        $param = $this->firstParameter(function (Carbon $carbon) {
            //
        });

        $exported = JavaScriptVariable::makeWithValue($param, new Carbon)->toTransportable()->toArray();

        $this->assertEquals(603448980, $exported['value']);
        $this->assertEquals(Carbon::class, $exported['meta']['class']);
    }

    #[Test]
    public function immutable_test()
    {
        $param = $this->firstParameter(function (CarbonImmutable $carbon) {
            //
        });

        $exported = JavaScriptVariable::makeWithValue($param, new CarbonImmutable)->toTransportable()->toArray();

        $this->assertEquals(CarbonImmutable::class, $exported['meta']['class']);
    }

    #[Test]
    public function base_class_test()
    {
        $param = $this->firstParameter(function (\Carbon\Carbon $carbon) {
            //
        });

        $exported = JavaScriptVariable::makeWithValue($param, new \Carbon\Carbon)->toTransportable()->toArray();

        $this->assertEquals(\Carbon\Carbon::class, $exported['meta']['class']);
    }

    #[Test]
    public function carbon_nullable_test()
    {
        $param = $this->firstParameter(function (?Carbon $carbon) {
            //
        });

        $exported = JavaScriptVariable::makeWithValue($param, null)->toTransportable()->toArray();

        $this->assertEquals(\Carbon\Carbon::class, $exported['meta']['class']);
    }
}
