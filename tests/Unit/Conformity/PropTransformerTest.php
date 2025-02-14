<?php

/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace Fusion\Tests\Unit\Conformity;

use Fusion\Conformity\Conformer;
use Fusion\Conformity\Transformers\PropTransformer;
use Fusion\Tests\Unit\Base;
use PHPUnit\Framework\Attributes\Test;
use Str;

class PropTransformerTest extends Base
{
    public function assertCodeMatches($content, $expected): void
    {
        $conformer = new Conformer($content, [PropTransformer::class]);

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
$username = prop(Auth::user()->name);
TXT;
        $expected = <<<'TXT'
$username = $this->prop(name: 'username', default: Auth::user()->name);
TXT;

        $this->assertCodeMatches($content, $expected);
    }

    #[Test]
    public function it_preserves_named_default_argument()
    {
        $content = <<<'TXT'
$username = prop(default: Auth::user()->name);
TXT;
        $expected = <<<'TXT'
$username = $this->prop(name: 'username', default: Auth::user()->name);
TXT;

        $this->assertCodeMatches($content, $expected);
    }

    #[Test]
    public function it_preserves_method_chains_and_adds_value_last()
    {
        $content = <<<'TXT'
$username = prop(default: Auth::user()->name)->readonly();
TXT;
        $expected = <<<'TXT'
$username = $this->prop(name: 'username', default: Auth::user()->name)->readonly();
TXT;

        $this->assertCodeMatches($content, $expected);
    }

    #[Test]
    public function it_handles_string_concatenation_in_default()
    {
        $content = <<<'TXT'
$message = prop('Hello ' . $name)->readonly();
TXT;
        $expected = <<<'TXT'
$message = $this->prop(name: 'message', default: 'Hello ' . $name)->readonly();
TXT;

        $this->assertCodeMatches($content, $expected);
    }

    #[Test]
    public function it_handles_arrow_functions_as_default()
    {
        $content = <<<'TXT'
$message = prop(fn() => 1);
TXT;
        $expected = <<<'TXT'
$message = $this->prop(name: 'message', default: fn() => 1);
TXT;

        $this->assertCodeMatches($content, $expected);
    }

    #[Test]
    public function it_handles_anonymous_functions_with_use()
    {
        $content = <<<'TXT'
$message = prop(function() use ($bar) { return $bar; });
TXT;

        $expected = <<<'TXT'
$message = $this->prop(name: 'message', default: function () use ($bar) {
    return $bar;
});
TXT;

        $this->assertCodeMatches($content, $expected);
    }

    #[Test]
    public function it_preserves_use_statements()
    {
        $content = <<<'TXT'
use Illuminate\Support\Facades\Auth;

$message = prop(function() use ($bar) { return $bar; });
TXT;
        $expected = <<<'TXT'
use Illuminate\Support\Facades\Auth;
$message = $this->prop(name: 'message', default: function () use ($bar) {
    return $bar;
});
TXT;

        $this->assertCodeMatches($content, $expected);
    }

    #[Test]
    public function it_preserves_use_statements_func()
    {
        $content = <<<'TXT'
use function Fusion\{prop, expose};

$message = prop(function() use ($bar) { return $bar; });
TXT;
        $expected = <<<'TXT'
use function Fusion\{prop, expose};
$message = $this->prop(name: 'message', default: function () use ($bar) {
    return $bar;
});
TXT;

        $this->assertCodeMatches($content, $expected);
    }

    #[Test]
    public function it_handles_prop_without_arguments()
    {
        $content = <<<'TXT'
$message = prop();
TXT;
        $expected = <<<'TXT'
$message = $this->prop(name: 'message');
TXT;

        $this->assertCodeMatches($content, $expected);
    }

    #[Test]
    public function it_handles_complex_expressions_in_default()
    {
        $content = <<<'TXT'
$config = prop(Config::get("app.name") . "-" . env("APP_ENV"));
TXT;
        $expected = <<<'TXT'
$config = $this->prop(name: 'config', default: Config::get("app.name") . "-" . env("APP_ENV"));
TXT;

        $this->assertCodeMatches($content, $expected);
    }

    #[Test]
    public function it_preserves_from_route()
    {
        $content = <<<'TXT'
$config = prop()->fromRoute();
TXT;
        $expected = <<<'TXT'
$config = $this->prop(name: 'config')->fromRoute();
TXT;

        $this->assertCodeMatches($content, $expected);
    }

    #[Test]
    public function it_preserves_from_route_params()
    {
        $content = <<<'TXT'
$config = prop()->fromRoute(withTrashed: true);
TXT;
        $expected = <<<'TXT'
$config = $this->prop(name: 'config')->fromRoute(withTrashed: true);
TXT;

        $this->assertCodeMatches($content, $expected);
    }

    #[Test]
    public function no_name()
    {
        $content = <<<'TXT'
$test = 1;

prop($test);
TXT;
        $expected = <<<'TXT'
$test = 1;
$this->prop(name: 'test', default: $test);
TXT;

        $this->assertCodeMatches($content, $expected);
    }

    #[Test]
    public function no_assign_but_name()
    {
        $content = <<<'TXT'
$test = 1;

prop($test, name: 'buzz');
TXT;
        $expected = <<<'TXT'
$test = 1;
$this->prop(name: 'buzz', default: $test);
TXT;

        $this->assertCodeMatches($content, $expected);
    }

    #[Test]
    public function no_assign_but_second_parameter()
    {
        $content = <<<'TXT'
$test = 1;

prop($test, 'buzz');
TXT;
        $expected = <<<'TXT'
$test = 1;
$this->prop($test, 'buzz');
TXT;

        $this->assertCodeMatches($content, $expected);
    }

    #[Test]
    public function no_assign_but_readonly()
    {
        $content = <<<'TXT'
prop(name: 'podcast', default: $podcast)->readonly();
TXT;
        $expected = <<<'TXT'
$this->prop(name: 'podcast', default: $podcast)->readonly();
TXT;

        $this->assertCodeMatches($content, $expected);
    }

    #[Test]
    public function no_assign_no_name_but_readonly()
    {
        $content = <<<'TXT'
prop($podcast)->readonly();
TXT;
        $expected = <<<'TXT'
$this->prop(name: 'podcast', default: $podcast)->readonly();
TXT;

        $this->assertCodeMatches($content, $expected);
    }
}
