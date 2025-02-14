<?php

/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace Fusion\Tests\Unit\Conformity;

use Exception;
use Fusion\Conformity\Conformer;
use Fusion\Conformity\Transformers\MountTransformer;
use Fusion\Tests\Unit\Base;
use PHPUnit\Framework\Attributes\Test;
use Str;

class MountTransformerTest extends Base
{
    public function assertCodeMatches($content, $expected): void
    {
        $conformer = new Conformer($content, [MountTransformer::class]);

        $actual = $conformer->conform();

        $this->assertEquals(
            $expected,
            Str::after($actual, "<?php\n\n"),
        );
    }

    #[Test]
    public function mount_1()
    {
        $content = <<<'TXT'
mount(function() {
    return 'buzz';
});
TXT;
        $expected = <<<'TXT'
$this->mount(function () {
    return 'buzz';
});
TXT;

        $this->assertCodeMatches($content, $expected);
    }

    #[Test]
    public function mount_2()
    {
        $content = <<<'TXT'
$buzz = mount(function() {
    return 'buzz';
});
TXT;
        $expected = <<<'TXT'
$buzz = $this->mount(function () {
    return 'buzz';
});
TXT;

        $this->assertCodeMatches($content, $expected);
    }

    #[Test]
    public function test_mount_with_arrow_function()
    {
        $content = 'mount(fn() => $this->fetchData());';
        $expected = '$this->mount(fn() => $this->fetchData());';

        $this->assertCodeMatches($content, $expected);
    }

    #[Test]
    public function test_mount_with_invalid_argument_type()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Mount function argument must be an anonymous function or arrow function.');

        $content = 'mount("not a function");';
        $this->assertCodeMatches($content, '');
    }

    #[Test]
    public function test_mount_with_multiple_arguments()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Mount function must have exactly one argument.');

        $content = 'mount(fn() => true, fn() => false);';
        $this->assertCodeMatches($content, '');
    }
}
