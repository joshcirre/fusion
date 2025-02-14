<?php

namespace Fusion\Tests\Unit\Conformity;

use Fusion\Conformity\Conformer;
use Fusion\Conformity\Transformers\ActionDiscoveryTransformer;
use Fusion\Conformity\Transformers\AnonymousClassTransformer;
use Fusion\Conformity\Transformers\AnonymousReturnTransformer;
use Fusion\Fusion;
use Fusion\Tests\Unit\Base;
use PHPUnit\Framework\Attributes\Test;
use Str;

class TrailingSemicolonTest extends Base
{
    public function assertCodeMatches($content, $expected): void
    {
        $conformer = new Conformer($content, [
            ActionDiscoveryTransformer::class,
            AnonymousReturnTransformer::class,
            AnonymousClassTransformer::class,
        ]);
        $conformer->setFilename(
            Fusion::storage('PHP/Bar/TestGenerated.php')
        );

        $actual = $conformer->conform();

        $this->assertEquals(
            $expected,
            Str::after($actual, "*/\n"),
        );
    }

    #[Test]
    public function well_formed_return_new()
    {
        $content = <<<'TXT'
return new class {
    public string $name = 'Aaron';
}
TXT;

        $expected = <<<'TXT'
namespace Fusion\Generated\Bar;

class TestGenerated extends \Fusion\FusionPage
{
    public string $name = 'Aaron';
}
TXT;

        $this->assertCodeMatches($content, $expected);
    }

    #[Test]
    public function no_return()
    {
        $content = <<<'TXT'
new class {
    public string $name = 'Aaron';
}
TXT;

        $expected = <<<'TXT'
namespace Fusion\Generated\Bar;

class TestGenerated extends \Fusion\FusionPage
{
    public string $name = 'Aaron';
}
TXT;

        $this->assertCodeMatches($content, $expected);
    }

    #[Test]
    public function existing_is_unmodified()
    {
        $content = <<<'TXT'
new class {
    public string $name = 'Aaron';
};
TXT;

        $expected = <<<'TXT'
namespace Fusion\Generated\Bar;

class TestGenerated extends \Fusion\FusionPage
{
    public string $name = 'Aaron';
}
TXT;

        $this->assertCodeMatches($content, $expected);
    }
}
