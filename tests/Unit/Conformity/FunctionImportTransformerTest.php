<?php

/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace Fusion\Tests\Unit\Conformity;

use Fusion\Conformity\Conformer;
use Fusion\Conformity\Transformers\FunctionImportTransformer;
use Fusion\Tests\Unit\Base;
use PHPUnit\Framework\Attributes\Test;
use Str;

class FunctionImportTransformerTest extends Base
{
    public function assertCodeMatches($content, $expected): void
    {
        $conformer = new Conformer($content, [FunctionImportTransformer::class]);

        $actual = $conformer->conform();

        $this->assertEquals(
            $expected,
            Str::after($actual, "<?php\n\n"),
        );
    }

    #[Test]
    public function it_removes_function_use_statements()
    {
        $content = <<<'TXT'
use function Fusion\{prop, expose};
$message = $this->prop(name: 'message', default: function () use ($bar) {
    return $bar;
});
TXT;
        $expected = <<<'TXT'
$message = $this->prop(name: 'message', default: function () use ($bar) {
    return $bar;
});
TXT;

        $this->assertCodeMatches($content, $expected);
    }

    #[Test]
    public function it_removes_single_function_imports()
    {
        $content = <<<'TXT'
use function Fusion\prop;
$message = $this->prop(name: 'message', default: 'Hello');
TXT;

        $expected = <<<'TXT'
$message = $this->prop(name: 'message', default: 'Hello');
TXT;

        $this->assertCodeMatches($content, $expected);
    }

    #[Test]
    public function it_removes_multiple_function_imports()
    {
        $content = <<<'TXT'
use function Fusion\prop;
use function Fusion\expose;
use function Fusion\mount;

$message = $this->prop(name: 'message', default: 'Hello');
TXT;

        $expected = <<<'TXT'
$message = $this->prop(name: 'message', default: 'Hello');
TXT;

        $this->assertCodeMatches($content, $expected);
    }

    #[Test]
    public function it_removes_grouped_function_imports()
    {
        $content = <<<'TXT'
use function Fusion\{prop, expose, mount};
$message = $this->prop(name: 'message', default: 'Hello');
TXT;

        $expected = <<<'TXT'
$message = $this->prop(name: 'message', default: 'Hello');
TXT;

        $this->assertCodeMatches($content, $expected);
    }

    #[Test]
    public function it_preserves_other_imports_in_group()
    {
        $content = <<<'TXT'
use Fusion\{prop, OtherClass, function expose, AnotherClass};
$message = $this->prop(name: 'message', default: 'Hello');
TXT;

        $expected = <<<'TXT'
use Fusion\{OtherClass, AnotherClass};
$message = $this->prop(name: 'message', default: 'Hello');
TXT;

        $this->assertCodeMatches($content, $expected);
    }

    #[Test]
    public function it_preserves_closures_using_variables()
    {
        $content = <<<'TXT'
use function Fusion\prop;
$bar = 'value';
$message = $this->prop(name: 'message', default: function () use ($bar) {
    return $bar;
});
TXT;

        $expected = <<<'TXT'
$bar = 'value';
$message = $this->prop(name: 'message', default: function () use ($bar) {
    return $bar;
});
TXT;

        $this->assertCodeMatches($content, $expected);
    }

    #[Test]
    public function it_preserves_other_function_imports()
    {
        $content = <<<'TXT'
use function Fusion\prop;
use function Illuminate\Support\{collect};
use function array_map;

$collection = collect([1, 2, 3]);
$message = $this->prop(name: 'message', default: 'Hello');
$mapped = array_map(fn($n) => $n * 2, [1, 2, 3]);
TXT;

        $expected = <<<'TXT'
use function Illuminate\Support\{collect};
use function array_map;
$collection = collect([1, 2, 3]);
$message = $this->prop(name: 'message', default: 'Hello');
$mapped = array_map(fn($n) => $n * 2, [1, 2, 3]);
TXT;

        $this->assertCodeMatches($content, $expected);
    }
}
