<?php

/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace Fusion\Tests\Unit\Conformity;

use Fusion\Conformity\Conformer;
use Fusion\Conformity\Transformers\PropValueTransformer;
use Fusion\Tests\Unit\Base;
use PHPUnit\Framework\Attributes\Test;
use Str;

class PropValueTransformerTest extends Base
{
    public function assertCodeMatches($content, $expected): void
    {
        $conformer = new Conformer($content, [PropValueTransformer::class]);

        $actual = $conformer->conform();

        $this->assertEquals(
            $expected,
            Str::after($actual, "<?php\n\n"),
        );
    }

    #[Test]
    public function it_transforms_unnamed_argument_to_default()
    {
        $content = <<<'TXT'
$username = $this->prop(name: 'username', default: Auth::user()->name);
TXT;
        $expected = <<<'TXT'
$username = $this->prop(name: 'username', default: Auth::user()->name)->value();
TXT;

        $this->assertCodeMatches($content, $expected);
    }

    #[Test]
    public function it_preserves_named_default_argument()
    {
        $content = <<<'TXT'
$username = $this->prop(name: 'username', default: Auth::user()->name);
TXT;
        $expected = <<<'TXT'
$username = $this->prop(name: 'username', default: Auth::user()->name)->value();
TXT;

        $this->assertCodeMatches($content, $expected);
    }

    #[Test]
    public function it_preserves_method_chains_and_adds_value_last()
    {
        $content = <<<'TXT'
$username = $this->prop(name: 'username', default: Auth::user()->name)->readonly();
TXT;
        $expected = <<<'TXT'
$username = $this->prop(name: 'username', default: Auth::user()->name)->readonly()->value();
TXT;

        $this->assertCodeMatches($content, $expected);
    }

    #[Test]
    public function it_handles_string_concatenation_in_default()
    {
        $content = <<<'TXT'
$message = $this->prop(name: 'message', default: 'Hello ' . $name)->readonly();
TXT;
        $expected = <<<'TXT'
$message = $this->prop(name: 'message', default: 'Hello ' . $name)->readonly()->value();
TXT;

        $this->assertCodeMatches($content, $expected);
    }

    #[Test]
    public function it_handles_arrow_functions_as_default()
    {
        $content = <<<'TXT'
$message = $this->prop(name: 'message', default: fn() => 1);
TXT;
        $expected = <<<'TXT'
$message = $this->prop(name: 'message', default: fn() => 1)->value();
TXT;

        $this->assertCodeMatches($content, $expected);
    }

    #[Test]
    public function it_handles_anonymous_functions_with_use()
    {
        $content = <<<'TXT'
$message = $this->prop(name: 'message', default: function () use ($bar) {
    return $bar;
});
TXT;

        $expected = <<<'TXT'
$message = $this->prop(name: 'message', default: function () use ($bar) {
    return $bar;
})->value();
TXT;

        $this->assertCodeMatches($content, $expected);
    }

    #[Test]
    public function it_preserves_use_statements()
    {
        $content = <<<'TXT'
use Illuminate\Support\Facades\Auth;
$message = $this->prop(name: 'message', default: function () use ($bar) {
    return $bar;
});
TXT;
        $expected = <<<'TXT'
use Illuminate\Support\Facades\Auth;
$message = $this->prop(name: 'message', default: function () use ($bar) {
    return $bar;
})->value();
TXT;

        $this->assertCodeMatches($content, $expected);
    }

    #[Test]
    public function it_handles_prop_without_arguments()
    {
        $content = <<<'TXT'
$message = $this->prop(name: 'message');
TXT;
        $expected = <<<'TXT'
$message = $this->prop(name: 'message')->value();
TXT;

        $this->assertCodeMatches($content, $expected);
    }

    #[Test]
    public function it_handles_complex_expressions_in_default()
    {
        $content = <<<'TXT'
$config = $this->prop(name: 'config', default: Config::get("app.name") . "-" . env("APP_ENV"));
TXT;
        $expected = <<<'TXT'
$config = $this->prop(name: 'config', default: Config::get("app.name") . "-" . env("APP_ENV"))->value();
TXT;

        $this->assertCodeMatches($content, $expected);
    }

    #[Test]
    public function it_preserves_from_route()
    {
        $content = <<<'TXT'
$config = $this->prop(name: 'config')->fromRoute();
TXT;
        $expected = <<<'TXT'
$config = $this->prop(name: 'config')->fromRoute()->value();
TXT;

        $this->assertCodeMatches($content, $expected);
    }

    #[Test]
    public function no_name()
    {
        $content = <<<'TXT'
$this->prop(name: 'test', default: $test);
TXT;
        $expected = <<<'TXT'
$this->prop(name: 'test', default: $test)->value();
TXT;

        $this->assertCodeMatches($content, $expected);
    }

    #[Test]
    public function no_assign_but_name()
    {
        $content = <<<'TXT'
$this->prop(name: 'buzz', default: $test);
TXT;
        $expected = <<<'TXT'
$this->prop(name: 'buzz', default: $test)->value();
TXT;

        $this->assertCodeMatches($content, $expected);
    }

    #[Test]
    public function no_assign_but_second_parameter()
    {
        $content = <<<'TXT'
$this->prop($test, 'buzz');
TXT;
        $expected = <<<'TXT'
$this->prop($test, 'buzz')->value();
TXT;

        $this->assertCodeMatches($content, $expected);
    }

    #[Test]
    public function no_assign_but_readonly()
    {
        $content = <<<'TXT'
$this->prop()->readonly();
TXT;
        $expected = <<<'TXT'
$this->prop()->readonly()->value();
TXT;

        $this->assertCodeMatches($content, $expected);
    }

    // PropValueTransformerTest.php
    public function test_handles_multiple_method_chains_preserving_order(): void
    {
        $content = <<<'TXT'
$url = $this->prop(name: 'url')->fromRoute('profile')->syncQueryString('profile_url')->readonly();
TXT;
        $expected = <<<'TXT'
$url = $this->prop(name: 'url')->fromRoute('profile')->syncQueryString('profile_url')->readonly()->value();
TXT;

        $this->assertCodeMatches($content, $expected);
    }

    public function test_handles_prop_inside_complex_expression(): void
    {
        $content = <<<'TXT'
$result = $someObject->process($this->prop(name: 'input')->readonly())->transform();
TXT;
        $expected = <<<'TXT'
$result = $someObject->process($this->prop(name: 'input')->readonly()->value())->transform();
TXT;

        $this->assertCodeMatches($content, $expected);
    }
}
