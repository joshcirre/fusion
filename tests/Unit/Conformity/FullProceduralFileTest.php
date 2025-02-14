<?php

/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace Fusion\Tests\Unit\Conformity;

use Fusion\Conformity\Conformer;
use Fusion\Fusion;
use Fusion\Tests\Unit\Base;
use PHPUnit\Framework\Attributes\Test;
use Str;

class FullProceduralFileTest extends Base
{
    public function assertCodeMatches($content, $expected): void
    {
        $conformer = new Conformer($content);
        $conformer->setFilename(
            Fusion::storage('PHP/Bar/TestGenerated.php')
        );
        $actual = $conformer->conform();

        $this->assertEquals(
            $expected,
            ltrim(Str::after($actual, '*/')),
        );
    }

    #[Test]
    public function basic_procedural()
    {
        $content = <<<'TXT'
$test = prop("hey");

$podcast = mount(fn(Podcast $podcast) => $podcast);

expose(favorite: function() use ($podcast) {
    $podcast->favorite();
});
TXT;

        $expected = <<<'TXT'
namespace Fusion\Generated\Bar;

class TestGenerated extends \Fusion\FusionPage
{
    #[\Fusion\Attributes\ServerOnly]
    public array $discoveredProps = ['test'];
    use \Fusion\Concerns\IsProceduralPage;
    public function runProceduralCode()
    {
        $test = $this->prop(name: 'test', default: "hey")->value();
        $podcast = $this->mount(fn(Podcast $podcast) => $podcast);
        $this->expose(favorite: function () use ($podcast) {
            $podcast->favorite();
        });
        $this->syncProps(get_defined_vars());
    }
    #[\Fusion\Attributes\Expose]
    public function favorite()
    {
        return call_user_func($this->actions[__FUNCTION__], ...func_get_args());
    }
}
TXT;

        $this->assertCodeMatches($content, $expected);
    }

    #[Test]
    public function missing_foreach()
    {
        $content = <<<'TXT'
use Symfony\Component\Finder\Finder;

// Create a Finder instance.
$files = new Finder()->files()->in(__DIR__)->name('*.vue');

$paths = prop([])->readonly();

// Iterate over the results and output each file's relative path.
foreach ($files as $file) {
    $paths[] = $file->getRelativePathname();
}
TXT;

        $expected = <<<'TXT'
namespace Fusion\Generated\Bar;

use Symfony\Component\Finder\Finder;
class TestGenerated extends \Fusion\FusionPage
{
    #[\Fusion\Attributes\ServerOnly]
    public array $discoveredProps = ['paths'];
    use \Fusion\Concerns\IsProceduralPage;
    public function runProceduralCode()
    {
        // Create a Finder instance.
        $files = (new Finder())->files()->in(__DIR__)->name('*.vue');
        $paths = $this->prop(name: 'paths', default: [])->readonly()->value();
        // Iterate over the results and output each file's relative path.
        foreach ($files as $file) {
            $paths[] = $file->getRelativePathname();
        }
        $this->syncProps(get_defined_vars());
    }
}
TXT;

        $this->assertCodeMatches($content, $expected);
    }
}
