<?php

/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace Fusion\Tests\Unit\Conformity;

use Exception;
use Fusion\Conformity\Conformer;
use Fusion\Conformity\Transformers\ExposeTransformer;
use Fusion\Tests\Unit\Base;
use PHPUnit\Framework\Attributes\Test;
use Str;

class ExposeTransformerTest extends Base
{
    public function assertCodeMatches($content, $expected): void
    {
        $conformer = new Conformer($content, [ExposeTransformer::class]);

        $actual = $conformer->conform();

        // dd($actual);

        $this->assertEquals(
            $expected,
            Str::after($actual, "<?php\n\n"),
        );
    }

    #[Test]
    public function expose_1()
    {
        $content = <<<'TXT'
expose(favorite: function() {
    return 'bar'; 
});
TXT;
        $expected = <<<'TXT'
$this->expose(favorite: function () {
    return 'bar';
});
TXT;

        $this->assertCodeMatches($content, $expected);
    }

    #[Test]
    public function expose_2()
    {
        $content = <<<'TXT'
expose(
    favorite: function() {
        return 'bar'; 
    },
    unfavorite: function() {
        return 'buz';
    }
);
TXT;
        $expected = <<<'TXT'
$this->expose(favorite: function () {
    return 'bar';
}, unfavorite: function () {
    return 'buz';
});
TXT;

        $this->assertCodeMatches($content, $expected);
    }

    #[Test]
    public function expose_3()
    {
        $content = <<<'TXT'
$favorite = expose(favorite: function() {
    return 'bar'; 
});
TXT;
        $expected = <<<'TXT'
TXT;

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Cannot assign the result of `expose` to a variable.');

        $this->assertCodeMatches($content, $expected);
    }

    #[Test]
    public function test_expose_with_arrow_function()
    {
        $content = 'expose(save: fn() => $this->save());';
        $expected = '$this->expose(save: fn() => $this->save());';

        $this->assertCodeMatches($content, $expected);
    }

    #[Test]
    public function test_expose_with_unnamed_argument()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Cannot expose an unnamed function.');

        $content = 'expose(function() { return true; });';
        $expected = '';  // Should fail or ignore unnamed arguments

        $this->assertCodeMatches($content, $expected);
    }

    #[Test]
    public function test_expose_with_use()
    {
        $content = <<<'TXT'
expose(favorite: function() use ($podcast) {
    $podcast->favorite();
});
TXT;

        $expected = <<<'TXT'
$this->expose(favorite: function () use ($podcast) {
    $podcast->favorite();
});
TXT;

        $this->assertCodeMatches($content, $expected);
    }

    public function test_expose_with_multiple_arrow_functions(): void
    {
        $content = <<<'TXT'
expose(
    save: fn() => $this->save(),
    delete: fn() => $this->delete(),
    archive: fn() => $this->archive()
);
TXT;
        $expected = <<<'TXT'
$this->expose(save: fn() => $this->save(), delete: fn() => $this->delete(), archive: fn() => $this->archive());
TXT;

        $this->assertCodeMatches($content, $expected);
    }

    public function test_expose_with_complex_closure(): void
    {
        $content = <<<'TXT'
expose(validate: function() use ($rules, $messages) {
    $validator = Validator::make(request()->all(), $rules, $messages);
    if ($validator->fails()) {
        throw new ValidationException($validator);
    }
    return true;
});
TXT;
        $expected = <<<'TXT'
$this->expose(validate: function () use ($rules, $messages) {
    $validator = Validator::make(request()->all(), $rules, $messages);
    if ($validator->fails()) {
        throw new ValidationException($validator);
    }
    return true;
});
TXT;

        $this->assertCodeMatches($content, $expected);
    }
}
