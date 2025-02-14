<?php

/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace Fusion\Tests\Unit\Conformity;

use Fusion\Conformity\Conformer;
use Fusion\Conformity\Transformers\ProceduralTransformer;
use Fusion\Tests\Unit\Base;
use PHPUnit\Framework\Attributes\Test;
use Str;

class ProceduralTransformerTest extends Base
{
    public function assertCodeMatches($content, $expected): void
    {
        $conformer = new Conformer($content, [ProceduralTransformer::class]);
        $actual = $conformer->conform();

        $this->assertEquals(
            $expected,
            Str::after($actual, "<?php\n\n"),
        );
    }

    #[Test]
    public function basic_procedural()
    {
        $content = '$test = "hey";';
        $expected = <<<'TXT'
return new class extends Fusion\FusionPage
{
    use \Fusion\Concerns\IsProceduralPage;
    public function runProceduralCode()
    {
        $test = "hey";
        $this->syncProps(get_defined_vars());
    }
};
TXT;

        $this->assertCodeMatches($content, $expected);
    }

    #[Test]
    public function use_statement_is_preserved()
    {
        $content = <<<'TXT'
use \App\Models\Podcast;

$test = "hey";
TXT;
        $expected = <<<'TXT'
use App\Models\Podcast;
return new class extends Fusion\FusionPage
{
    use \Fusion\Concerns\IsProceduralPage;
    public function runProceduralCode()
    {
        $test = "hey";
        $this->syncProps(get_defined_vars());
    }
};
TXT;

        $this->assertCodeMatches($content, $expected);
    }
}
