<?php

/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace Fusion\Tests\Unit\Conformity;

use Fusion\Conformity\Conformer;
use Fusion\Conformity\Transformers\AnonymousReturnTransformer;
use Fusion\Fusion;
use Fusion\Tests\Unit\Base;
use PHPUnit\Framework\Attributes\Test;
use Str;

class AnonymousReturnTransformerTest extends Base
{
    public function assertCodeMatches($content, $expected): void
    {
        $conformer = new Conformer($content, [AnonymousReturnTransformer::class]);
        $conformer->setFilename(
            Fusion::storage('PHP/Bar/TestGenerated.php')
        );

        $actual = $conformer->conform();

        $this->assertEquals(
            $expected,
            Str::after($actual, "<?php\n\n"),
        );
    }

    #[Test]
    public function standalone_anonymous_class()
    {
        $content = <<<'TXT'
new class {
    public \App\Models\Podcast $podcast;
};
TXT;

        $expected = <<<'TXT'
return new class
{
    public \App\Models\Podcast $podcast;
};
TXT;

        $this->assertCodeMatches($content, $expected);
    }

    #[Test]
    public function anonymous_class_with_method()
    {
        $content = <<<'TXT'
new class {
    public \App\Models\Podcast $podcast;

    public function mount(\App\Models\Podcast $podcast) {
        $this->podcast = $podcast;
    }
};
TXT;

        $expected = <<<'TXT'
return new class
{
    public \App\Models\Podcast $podcast;
    public function mount(\App\Models\Podcast $podcast)
    {
        $this->podcast = $podcast;
    }
};
TXT;

        $this->assertCodeMatches($content, $expected);
    }

    #[Test]
    public function anonymous_class_with_use_statement()
    {
        $content = <<<'TXT'
use App\Models\Podcast;

new class {
    public Podcast $podcast;

    public function mount(Podcast $podcast)
    {
        $this->podcast = $podcast;
    }
};
TXT;

        $expected = <<<'TXT'
use App\Models\Podcast;
return new class
{
    public Podcast $podcast;
    public function mount(Podcast $podcast)
    {
        $this->podcast = $podcast;
    }
};
TXT;

        $this->assertCodeMatches($content, $expected);
    }

    #[Test]
    public function already_returned_anonymous_class()
    {
        $content = <<<'TXT'
return new class {
    public \App\Models\Podcast $podcast;
};
TXT;

        $expected = <<<'TXT'
return new class
{
    public \App\Models\Podcast $podcast;
};
TXT;

        $this->assertCodeMatches($content, $expected);
    }

    #[Test]
    public function anonymous_class_with_extends()
    {
        $content = <<<'TXT'
new class extends \Fusion\FusionPage {
    public \App\Models\Podcast $podcast;
};
TXT;

        $expected = <<<'TXT'
return new class extends \Fusion\FusionPage
{
    public \App\Models\Podcast $podcast;
};
TXT;

        $this->assertCodeMatches($content, $expected);
    }
}
