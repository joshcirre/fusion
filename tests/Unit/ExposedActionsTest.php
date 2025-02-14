<?php

namespace Fusion\Tests\Unit;

use Fusion\Attributes\ServerOnly;
use Fusion\FusionPage;
use PHPUnit\Framework\Attributes\Test;

class ExposedActionsTest extends Base
{
    #[Test]
    public function it_exposes_the_right_functions(): void
    {
        $methods = (new ExampleConformedClass)->reflector->exposedActionMethods();

        $methods = collect($methods)->map->name->toArray();

        $this->assertEquals(['favorite', 'fusionSync'], $methods);
    }
}

class ExampleConformedClass extends FusionPage
{
    #[ServerOnly]
    public function hidden() {}

    public function runProceduralCode() {}

    public function mount() {}

    public function favorite() {}
}
