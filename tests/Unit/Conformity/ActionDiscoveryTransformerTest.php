<?php

namespace Fusion\Tests\Unit\Conformity;

use Fusion\Conformity\Conformer;
use Fusion\Conformity\Transformers\ActionDiscoveryTransformer;
use Fusion\Tests\Unit\Base;
use PHPUnit\Framework\Attributes\Test;
use Str;

class ActionDiscoveryTransformerTest extends Base
{
    public function assertCodeMatches($content, $expected): void
    {
        $conformer = new Conformer($content, [ActionDiscoveryTransformer::class]);
        $actual = $conformer->conform();

        $this->assertEquals($expected, Str::after($actual, "<?php\n\n"));
    }

    #[Test]
    public function it_discovers_single_action()
    {
        $content = <<<'TXT'
use Fusion\Concerns\IsProceduralPage;
return new class
{
    use IsProceduralPage;
    
    public function runProceduralCode()
    {
        $this->expose(unfavorite: function () {
            return 'test';
        });
        $this->syncProps(get_defined_vars());
    }
};
TXT;

        $expected = <<<'TXT'
use Fusion\Concerns\IsProceduralPage;
return new class
{
    use IsProceduralPage;
    public function runProceduralCode()
    {
        $this->expose(unfavorite: function () {
            return 'test';
        });
        $this->syncProps(get_defined_vars());
    }
    #[\Fusion\Attributes\Expose]
    public function unfavorite()
    {
        return call_user_func($this->actions[__FUNCTION__], ...func_get_args());
    }
};
TXT;

        $this->assertCodeMatches($content, $expected);
    }

    #[Test]
    public function it_discovers_multiple_actions()
    {
        $content = <<<'TXT'
use Fusion\Concerns\IsProceduralPage;
return new class
{
    use IsProceduralPage;
    public function runProceduralCode()
    {
        $this->expose(unfavorite: fn() => 'test');
        $this->expose(like: fn() => true);
        $this->syncProps(get_defined_vars());
    }
};
TXT;

        $expected = <<<'TXT'
use Fusion\Concerns\IsProceduralPage;
return new class
{
    use IsProceduralPage;
    public function runProceduralCode()
    {
        $this->expose(unfavorite: fn() => 'test');
        $this->expose(like: fn() => true);
        $this->syncProps(get_defined_vars());
    }
    #[\Fusion\Attributes\Expose]
    public function unfavorite()
    {
        return call_user_func($this->actions[__FUNCTION__], ...func_get_args());
    }
    #[\Fusion\Attributes\Expose]
    public function like()
    {
        return call_user_func($this->actions[__FUNCTION__], ...func_get_args());
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
        $this->expose(test: fn() => true);
        $this->syncProps(get_defined_vars());
    }
};
TXT;

        $this->assertCodeMatches($content, $content);
    }

    #[Test]
    public function it_works_with_fully_qualified_trait()
    {
        $content = <<<'TXT'
return new class
{
    use \Fusion\Concerns\IsProceduralPage;
    
    public function runProceduralCode()
    {
        $this->expose(test: fn() => true);
        $this->syncProps(get_defined_vars());
    }
};
TXT;

        $expected = <<<'TXT'
return new class
{
    use \Fusion\Concerns\IsProceduralPage;
    public function runProceduralCode()
    {
        $this->expose(test: fn() => true);
        $this->syncProps(get_defined_vars());
    }
    #[\Fusion\Attributes\Expose]
    public function test()
    {
        return call_user_func($this->actions[__FUNCTION__], ...func_get_args());
    }
};
TXT;

        $this->assertCodeMatches($content, $expected);
    }

    #[Test]
    public function it_handles_simple_typed_arguments()
    {
        $content = <<<'TXT'
use Fusion\Concerns\IsProceduralPage;
return new class
{
    use IsProceduralPage;
    public function runProceduralCode()
    {
        $this->expose(test: fn(string $nt) => true);
        $this->syncProps(get_defined_vars());
    }
};
TXT;

        $expected = <<<'TXT'
use Fusion\Concerns\IsProceduralPage;
return new class
{
    use IsProceduralPage;
    public function runProceduralCode()
    {
        $this->expose(test: fn(string $nt) => true);
        $this->syncProps(get_defined_vars());
    }
    #[\Fusion\Attributes\Expose]
    public function test(string $nt)
    {
        return call_user_func($this->actions[__FUNCTION__], ...func_get_args());
    }
};
TXT;

        $this->assertCodeMatches($content, $expected);
    }

    #[Test]
    public function it_handles_nullable_types()
    {
        $content = <<<'TXT'
use Fusion\Concerns\IsProceduralPage;
return new class
{
    use IsProceduralPage;
    public function runProceduralCode()
    {
        $this->expose(test: fn(?string $int = null) => true);
        $this->syncProps(get_defined_vars());
    }
};
TXT;

        $expected = <<<'TXT'
use Fusion\Concerns\IsProceduralPage;
return new class
{
    use IsProceduralPage;
    public function runProceduralCode()
    {
        $this->expose(test: fn(?string $int = null) => true);
        $this->syncProps(get_defined_vars());
    }
    #[\Fusion\Attributes\Expose]
    public function test(?string $int = null)
    {
        return call_user_func($this->actions[__FUNCTION__], ...func_get_args());
    }
};
TXT;

        $this->assertCodeMatches($content, $expected);
    }

    #[Test]
    public function it_handles_union_types()
    {
        $content = <<<'TXT'
use Fusion\Concerns\IsProceduralPage;
return new class
{
    use IsProceduralPage;
    public function runProceduralCode()
    {
        $this->expose(test: function(string|int $id, array|null $data = null) {
            return true;
        });
        $this->syncProps(get_defined_vars());
    }
};
TXT;

        $expected = <<<'TXT'
use Fusion\Concerns\IsProceduralPage;
return new class
{
    use IsProceduralPage;
    public function runProceduralCode()
    {
        $this->expose(test: function (string|int $id, array|null $data = null) {
            return true;
        });
        $this->syncProps(get_defined_vars());
    }
    #[\Fusion\Attributes\Expose]
    public function test(string|int $id, array|null $data = null)
    {
        return call_user_func($this->actions[__FUNCTION__], ...func_get_args());
    }
};
TXT;

        $this->assertCodeMatches($content, $expected);
    }

    #[Test]
    public function it_handles_variadic_arguments()
    {
        $content = <<<'TXT'
use Fusion\Concerns\IsProceduralPage;
return new class
{
    use IsProceduralPage;
    public function runProceduralCode()
    {
        $this->expose(test: fn(string $event, ...$args) => true);
        $this->syncProps(get_defined_vars());
    }
};
TXT;

        $expected = <<<'TXT'
use Fusion\Concerns\IsProceduralPage;
return new class
{
    use IsProceduralPage;
    public function runProceduralCode()
    {
        $this->expose(test: fn(string $event, ...$args) => true);
        $this->syncProps(get_defined_vars());
    }
    #[\Fusion\Attributes\Expose]
    public function test(string $event, ...$args)
    {
        return call_user_func($this->actions[__FUNCTION__], ...func_get_args());
    }
};
TXT;

        $this->assertCodeMatches($content, $expected);
    }

    #[Test]
    public function it_handles_complex_type_combinations()
    {
        $content = <<<'TXT'
use Fusion\Concerns\IsProceduralPage;
return new class
{
    use IsProceduralPage;
    public function runProceduralCode()
    {
        $this->expose(test: function (?string $name = null, int|float $number, array ...$items) {
            return true;
        });
        $this->syncProps(get_defined_vars());
    }
};
TXT;

        $expected = <<<'TXT'
use Fusion\Concerns\IsProceduralPage;
return new class
{
    use IsProceduralPage;
    public function runProceduralCode()
    {
        $this->expose(test: function (?string $name = null, int|float $number, array ...$items) {
            return true;
        });
        $this->syncProps(get_defined_vars());
    }
    #[\Fusion\Attributes\Expose]
    public function test(?string $name = null, int|float $number, array ...$items)
    {
        return call_user_func($this->actions[__FUNCTION__], ...func_get_args());
    }
};
TXT;

        $this->assertCodeMatches($content, $expected);
    }

    #[Test]
    public function it_handles_default_values()
    {
        $content = <<<'TXT'
use Fusion\Concerns\IsProceduralPage;
return new class
{
    use IsProceduralPage;
    public function runProceduralCode()
    {
        $this->expose(test: fn(string $type = 'post', int $limit = 10) => true);
        $this->syncProps(get_defined_vars());
    }
};
TXT;

        $expected = <<<'TXT'
use Fusion\Concerns\IsProceduralPage;
return new class
{
    use IsProceduralPage;
    public function runProceduralCode()
    {
        $this->expose(test: fn(string $type = 'post', int $limit = 10) => true);
        $this->syncProps(get_defined_vars());
    }
    #[\Fusion\Attributes\Expose]
    public function test(string $type = 'post', int $limit = 10)
    {
        return call_user_func($this->actions[__FUNCTION__], ...func_get_args());
    }
};
TXT;

        $this->assertCodeMatches($content, $expected);
    }

    #[Test]
    public function it_handles_array_type_with_default_empty_array()
    {
        $content = <<<'TXT'
use Fusion\Concerns\IsProceduralPage;
return new class
{
    use IsProceduralPage;
    public function runProceduralCode()
    {
        $this->expose(test: fn(array $options = []) => true);
        $this->syncProps(get_defined_vars());
    }
};
TXT;

        $expected = <<<'TXT'
use Fusion\Concerns\IsProceduralPage;
return new class
{
    use IsProceduralPage;
    public function runProceduralCode()
    {
        $this->expose(test: fn(array $options = []) => true);
        $this->syncProps(get_defined_vars());
    }
    #[\Fusion\Attributes\Expose]
    public function test(array $options = [])
    {
        return call_user_func($this->actions[__FUNCTION__], ...func_get_args());
    }
};
TXT;

        $this->assertCodeMatches($content, $expected);
    }

    #[Test]
    public function it_handles_mixed_type()
    {
        $content = <<<'TXT'
use Fusion\Concerns\IsProceduralPage;
return new class
{
    use IsProceduralPage;
    public function runProceduralCode()
    {
        $this->expose(test: fn(mixed $data) => true);
        $this->syncProps(get_defined_vars());
    }
};
TXT;

        $expected = <<<'TXT'
use Fusion\Concerns\IsProceduralPage;
return new class
{
    use IsProceduralPage;
    public function runProceduralCode()
    {
        $this->expose(test: fn(mixed $data) => true);
        $this->syncProps(get_defined_vars());
    }
    #[\Fusion\Attributes\Expose]
    public function test(mixed $data)
    {
        return call_user_func($this->actions[__FUNCTION__], ...func_get_args());
    }
};
TXT;

        $this->assertCodeMatches($content, $expected);
    }

    #[Test]
    public function it_handles_intersection_types()
    {
        $content = <<<'TXT'
use Fusion\Concerns\IsProceduralPage;
return new class
{
    use IsProceduralPage;
    public function runProceduralCode()
    {
        $this->expose(test: function(Iterator&Countable $collection) {
            return true;
        });
        $this->syncProps(get_defined_vars());
    }
};
TXT;

        $expected = <<<'TXT'
use Fusion\Concerns\IsProceduralPage;
return new class
{
    use IsProceduralPage;
    public function runProceduralCode()
    {
        $this->expose(test: function (Iterator&Countable $collection) {
            return true;
        });
        $this->syncProps(get_defined_vars());
    }
    #[\Fusion\Attributes\Expose]
    public function test(Iterator&Countable $collection)
    {
        return call_user_func($this->actions[__FUNCTION__], ...func_get_args());
    }
};
TXT;

        $this->assertCodeMatches($content, $expected);
    }

    #[Test]
    public function it_handles_readonly_properties()
    {
        $content = <<<'TXT'
use Fusion\Concerns\IsProceduralPage;
return new class
{
    use IsProceduralPage;
    public function runProceduralCode()
    {
        $this->expose(test: fn(readonly User $user) => true);
        $this->syncProps(get_defined_vars());
    }
};
TXT;

        $expected = <<<'TXT'
use Fusion\Concerns\IsProceduralPage;
return new class
{
    use IsProceduralPage;
    public function runProceduralCode()
    {
        $this->expose(test: fn(readonly User $user) => true);
        $this->syncProps(get_defined_vars());
    }
    #[\Fusion\Attributes\Expose]
    public function test(readonly User $user)
    {
        return call_user_func($this->actions[__FUNCTION__], ...func_get_args());
    }
};
TXT;

        $this->assertCodeMatches($content, $expected);
    }

    #[Test]
    public function it_handles_readonly_type_and_default(): void
    {
        $content = <<<'TXT'
use Fusion\Concerns\IsProceduralPage;
return new class
{
    use IsProceduralPage;
    public function runProceduralCode()
    {
        $this->expose(test: fn(readonly User $user = null) => true);
        $this->syncProps(get_defined_vars());
    }
};
TXT;

        $expected = <<<'TXT'
use Fusion\Concerns\IsProceduralPage;
return new class
{
    use IsProceduralPage;
    public function runProceduralCode()
    {
        $this->expose(test: fn(readonly User $user = null) => true);
        $this->syncProps(get_defined_vars());
    }
    #[\Fusion\Attributes\Expose]
    public function test(readonly User $user = null)
    {
        return call_user_func($this->actions[__FUNCTION__], ...func_get_args());
    }
};
TXT;

        $this->assertCodeMatches($content, $expected);
    }
}
