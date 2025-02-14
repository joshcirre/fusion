<?php

/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace Fusion\Tests\Unit\Routing;

use Fusion\Routing\Registrar;
use Fusion\Tests\Unit\Base;
use PHPUnit\Framework\Attributes\Test;

class RouteOrderTest extends Base
{
    #[Test]
    public function base_test()
    {
        $files = [
            'foo/bar/Index.vue' => 'Index.vue',
            'foo/bar/[model].vue' => '[model].vue',
            'foo/bar/[...rest].vue' => '[...rest].vue',
            'foo/bar/podcasts/index.vue' => 'podcasts/index.vue',
            'foo/bar/podcasts/create.vue' => 'podcasts/create.vue',
            'foo/bar/podcasts/[podcast].vue' => 'podcasts/[podcast].vue',
            'foo/bar/podcasts/[podcast]/notes.vue' => 'podcasts/[podcast]/notes.vue',
            'foo/bar/podcasts/[podcast]/notes/[note].vue' => 'podcasts/[podcast]/notes/[note].vue',
            'foo/bar/podcasts/[...wild].vue' => 'podcasts/[...wild].vue',
            'foo/bar/episodes/[...wild].vue' => 'episodes/[...wild].vue',
            'foo/bar/episodes/index.vue' => 'episodes/index.vue',
            'foo/bar/episodes/[episode].vue' => 'episodes/[episode].vue',
        ];

        $registrar = new Registrar;
        for ($i = 0; $i < 10; $i++) {
            shuffle($files);

            $this->assertEquals([
                '',
                '/episodes',
                '/podcasts',
                '/podcasts/create',
                '/episodes/{episode}',
                '/podcasts/{podcast}',
                '/podcasts/{podcast}/notes',
                '/podcasts/{podcast}/notes/{note}',
                '/{model}',
                '/episodes/{wild}',
                '/podcasts/{wild}',
                '/{rest}',
            ], $registrar->mapFiles('/', $files)->pluck('uri')->toArray());
        }
    }

    #[Test]
    public function demo_fail()
    {
        $files = [
            'resources/js/Pages/Index.vue' => 'Index.vue',
            'resources/js/Pages/[Podcast].vue' => '[Podcast].vue',
            'resources/js/Pages/[...ids].vue' => '[...ids].vue',
        ];

        $registrar = new Registrar;

        for ($i = 0; $i < 10; $i++) {
            shuffle($files);

            $this->assertEquals([
                '',
                '/{podcast}',
                '/{ids}',
            ], $registrar->mapFiles('/', $files)->pluck('uri')->toArray());
        }
    }
}
