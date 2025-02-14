<?php

/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace Fusion\Tests\Unit\Conformity;

use Fusion\Conformity\Conformer;
use Fusion\Conformity\Transformers\AnonymousClassTransformer;
use Fusion\Fusion;
use Fusion\Tests\Unit\Base;
use PHPUnit\Framework\Attributes\Test;
use Str;

class AnonymousClassTransformerTest extends Base
{
    public function assertCodeMatches($content, $expected): void
    {
        $conformer = new Conformer($content, [AnonymousClassTransformer::class]);
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

    #[Test]
    public function anonymous_class_that_extends_page()
    {
        $content = <<<'TXT'
return new class extends \Fusion\FusionPage {
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

    #[Test]
    public function anonymous_class_with_imports()
    {
        $content = <<<'TXT'
use App\Something\SomethingElse;

return new class extends \Fusion\FusionPage {
    public string $name = 'Aaron';
};
TXT;

        $expected = <<<'TXT'
namespace Fusion\Generated\Bar;

use App\Something\SomethingElse;
class TestGenerated extends \Fusion\FusionPage
{
    public string $name = 'Aaron';
}
TXT;

        $this->assertCodeMatches($content, $expected);
    }

    #[Test]
    public function anonymous_class_with_aliased_import()
    {
        $content = <<<'TXT'
use App\Something\SomethingElse as Foobar;

return new class extends \Fusion\FusionPage {
    public string $name = 'Aaron';
};
TXT;

        $expected = <<<'TXT'
namespace Fusion\Generated\Bar;

use App\Something\SomethingElse as Foobar;
class TestGenerated extends \Fusion\FusionPage
{
    public string $name = 'Aaron';
}
TXT;

        $this->assertCodeMatches($content, $expected);
    }

    #[Test]
    public function test_preserves_multiple_method_declarations(): void
    {
        $content = <<<'TXT'
use App\Models\User;

return new class {
    public string $name = 'Aaron';
    
    public function validateUser(User $user): bool 
    {
        return $user->isActive();
    }
    
    public function processUser(User $user): void 
    {
        $user->process();
    }
};
TXT;

        $expected = <<<'TXT'
namespace Fusion\Generated\Bar;

use App\Models\User;
class TestGenerated extends \Fusion\FusionPage
{
    public string $name = 'Aaron';
    public function validateUser(User $user): bool
    {
        return $user->isActive();
    }
    public function processUser(User $user): void
    {
        $user->process();
    }
}
TXT;

        $this->assertCodeMatches($content, $expected);
    }
}
