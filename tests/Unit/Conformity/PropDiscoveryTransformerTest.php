<?php

namespace Fusion\Tests\Unit\Conformity;

use Fusion\Conformity\Conformer;
use Fusion\Conformity\Transformers\PropDiscoveryTransformer;
use Fusion\Tests\Unit\Base;
use PHPUnit\Framework\Attributes\Test;
use Str;

class PropDiscoveryTransformerTest extends Base
{
    public function assertCodeMatches($content, $expected): void
    {
        $conformer = new Conformer($content, [PropDiscoveryTransformer::class]);
        $actual = $conformer->conform();

        $this->assertEquals($expected, Str::after($actual, "<?php\n\n"));
    }

    #[Test]
    public function it_discovers_single_prop_name()
    {
        $content = <<<'TXT'
use Fusion\Concerns\IsProceduralPage;

return new class {
    use IsProceduralPage;
    
    public function runProceduralCode()
    {
        $test = $this->prop(name: 'test')->value();
        $this->syncProps(get_defined_vars());
    }
};
TXT;

        $expected = <<<'TXT'
use Fusion\Concerns\IsProceduralPage;
return new class
{
    #[\Fusion\Attributes\ServerOnly]
    public array $discoveredProps = ['test'];
    use IsProceduralPage;
    public function runProceduralCode()
    {
        $test = $this->prop(name: 'test')->value();
        $this->syncProps(get_defined_vars());
    }
};
TXT;

        $this->assertCodeMatches($content, $expected);
    }

    #[Test]
    public function it_discovers_multiple_prop_names()
    {
        $content = <<<'TXT'
use Fusion\Concerns\IsProceduralPage;

return new class {
    use IsProceduralPage;
    
    public function runProceduralCode()
    {
        $name = $this->prop(name: 'name')->value();
        $email = $this->prop(name: 'email')->value();
        $this->syncProps(get_defined_vars());
    }
};
TXT;

        $expected = <<<'TXT'
use Fusion\Concerns\IsProceduralPage;
return new class
{
    #[\Fusion\Attributes\ServerOnly]
    public array $discoveredProps = ['name', 'email'];
    use IsProceduralPage;
    public function runProceduralCode()
    {
        $name = $this->prop(name: 'name')->value();
        $email = $this->prop(name: 'email')->value();
        $this->syncProps(get_defined_vars());
    }
};
TXT;

        $this->assertCodeMatches($content, $expected);
    }

    #[Test]
    public function it_ignores_classes_without_procedural_trait()
    {
        $content = <<<'TXT'
return new class
{
    public function runProceduralCode()
    {
        $test = $this->prop(name: 'test')->value();
        $this->syncProps(get_defined_vars());
    }
};
TXT;

        $this->assertCodeMatches($content, $content);
    }

    #[Test]
    public function it_eliminates_duplicate_prop_names()
    {
        $content = <<<'TXT'
use Fusion\Concerns\IsProceduralPage;

return new class
{
    use IsProceduralPage;
    
    public function runProceduralCode()
    {
        $test = $this->prop(name: 'test')->value();
        $test2 = $this->prop(name: 'test')->value();
        $this->syncProps(get_defined_vars());
    }
};
TXT;

        $expected = <<<'TXT'
use Fusion\Concerns\IsProceduralPage;
return new class
{
    #[\Fusion\Attributes\ServerOnly]
    public array $discoveredProps = ['test'];
    use IsProceduralPage;
    public function runProceduralCode()
    {
        $test = $this->prop(name: 'test')->value();
        $test2 = $this->prop(name: 'test')->value();
        $this->syncProps(get_defined_vars());
    }
};
TXT;

        $this->assertCodeMatches($content, $expected);
    }

    #[Test]
    public function it_works_with_method_chaining()
    {
        $content = <<<'TXT'
use Fusion\Concerns\IsProceduralPage;

return new class
{
    use IsProceduralPage;
    
    public function runProceduralCode()
    {
        $test = $this->prop(name: 'test')->syncQueryString()->value();
        $this->syncProps(get_defined_vars());
    }
};
TXT;

        $expected = <<<'TXT'
use Fusion\Concerns\IsProceduralPage;
return new class
{
    #[\Fusion\Attributes\ServerOnly]
    public array $discoveredProps = ['test'];
    use IsProceduralPage;
    public function runProceduralCode()
    {
        $test = $this->prop(name: 'test')->syncQueryString()->value();
        $this->syncProps(get_defined_vars());
    }
};
TXT;

        $this->assertCodeMatches($content, $expected);
    }

    #[Test]
    public function it_works_with_fully_qualified_trait()
    {
        $content = <<<'TXT'
return new class {
    use \Fusion\Concerns\IsProceduralPage;
    
    public function runProceduralCode()
    {
        $test = $this->prop(name: 'test')->value();
        $this->syncProps(get_defined_vars());
    }
};
TXT;

        $expected = <<<'TXT'
return new class
{
    #[\Fusion\Attributes\ServerOnly]
    public array $discoveredProps = ['test'];
    use \Fusion\Concerns\IsProceduralPage;
    public function runProceduralCode()
    {
        $test = $this->prop(name: 'test')->value();
        $this->syncProps(get_defined_vars());
    }
};
TXT;

        $this->assertCodeMatches($content, $expected);
    }

    // PropDiscoveryTransformerTest.php
    public function it_handles_deeply_nested_props(): void
    {
        $content = <<<'TXT'
use Fusion\Concerns\IsProceduralPage;

return new class
{
    use IsProceduralPage;
    
    public function runProceduralCode()
    {
        if ($condition) {
            foreach ($items as $item) {
                while($running) {
                    $test = $this->prop(name: 'test')->value();
                }
            }
        }
        $this->syncProps(get_defined_vars());
    }
};
TXT;

        $expected = <<<'TXT'
use Fusion\Concerns\IsProceduralPage;
return new class
{
    #[\Fusion\Attributes\ServerOnly]
    public array $discoveredProps = ['test'];
    use IsProceduralPage;
    public function runProceduralCode()
    {
        if ($condition) {
            foreach ($items as $item) {
                while($running) {
                    $test = $this->prop(name: 'test')->value();
                }
            }
        }
        $this->syncProps(get_defined_vars());
    }
};
TXT;

        $this->assertCodeMatches($content, $expected);
    }
}
