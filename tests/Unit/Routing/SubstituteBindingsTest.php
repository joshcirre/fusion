<?php

/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace Fusion\Tests\Unit\Routing;

use Fusion\Routing\SubstituteBindings;
use Fusion\Tests\Unit\Base;
use Illuminate\Contracts\Routing\UrlRoutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Routing\Exceptions\BackedEnumCaseNotFoundException;
use Illuminate\Routing\Router;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;

class SubstituteBindingsTest extends Base
{
    #[Test]
    public function it_resolves_url_routable_models()
    {
        $binder = new SubstituteBindings([
            'model' => [
                'class' => ExampleModel::class
            ],
        ]);

        $result = $binder->resolve([
            'model' => 1
        ]);

        $this->assertInstanceOf(ExampleModel::class, $result['model']);
    }

    #[Test]
    public function it_throws_model_not_found_exception_when_model_cannot_be_resolved()
    {
        $this->expectException(ModelNotFoundException::class);

        $binder = new SubstituteBindings([
            'model' => [
                'class' => ExampleModel::class
            ]
        ]);

        $binder->resolve([
            'model' => 2
        ]);
    }

    #[Test]
    public function it_resolves_soft_deleted_models()
    {
        $binder = new SubstituteBindings([
            'model' => [
                'class' => ExampleModel::class,
                'withTrashed' => true
            ]
        ]);

        $result = $binder->resolve([
            'model' => 2
        ]);

        $this->assertInstanceOf(ExampleModel::class, $result['model']);
    }

    #[Test]
    public function it_resolves_backed_enums()
    {
        $binder = new SubstituteBindings([
            'status' => [
                'class' => ExampleStatus::class
            ]
        ]);

        $result = $binder->resolve(['status' => 'draft']);

        $this->assertInstanceOf(ExampleStatus::class, $result['status']);
        $this->assertEquals(ExampleStatus::DRAFT, $result['status']);
    }

    #[Test]
    public function it_throws_exception_for_invalid_enum_value()
    {
        $this->expectException(BackedEnumCaseNotFoundException::class);

        $binder = new SubstituteBindings([
            'status' => [
                'class' => ExampleStatus::class
            ]
        ]);

        $binder->resolve([
            'status' => 'invalid'
        ]);
    }

    #[Test]
    public function it_uses_explicit_router_binding_when_available()
    {
        app(Router::class)->bind('custom', function () {
            return 'explicit-result';
        });

        $binder = new SubstituteBindings([
            'custom' => []
        ]);

        $result = $binder->resolve([
            'custom' => 'value'
        ]);

        $this->assertEquals('explicit-result', $result['custom']);
    }

    #[Test]
    public function it_skips_parameters_without_bindings()
    {
        $binder = new SubstituteBindings([]);

        $result = $binder->resolve(['unbound' => 'value']);

        $this->assertEquals('value', $result['unbound']);
    }

    #[Test]
    public function it_uses_custom_key_for_model_binding()
    {
        $binder = new SubstituteBindings([
            'model' => [
                'class' => ExampleModel::class,
                'key' => 'slug'
            ]
        ]);

        $result = $binder->resolve([
            'model' => 'custom-slug'
        ]);

        $this->assertInstanceOf(ExampleModel::class, $result['model']);
    }

    #[Test]
    public function custom_key_not_found()
    {
        $this->expectException(ModelNotFoundException::class);

        $binder = new SubstituteBindings([
            'model' => [
                'class' => ExampleModel::class,
                'key' => 'slug'
            ]
        ]);

        $result = $binder->resolve([
            'model' => 'baz buz'
        ]);
    }

    #[Test]
    public function it_throws_exception_for_non_existent_model_class()
    {
        $this->expectException(InvalidArgumentException::class);

        $binder = new SubstituteBindings([
            'model' => ['class' => 'NonExistentClass']
        ]);

        $binder->resolve(['model' => 1]);
    }

    #[Test]
    public function it_handles_multiple_parameters_correctly()
    {
        $binder = new SubstituteBindings([
            'model' => ['class' => ExampleModel::class],
            'status' => ['class' => ExampleStatus::class],
            'unbound' => ['class' => 'UnboundClass']
        ]);

        $result = $binder->resolve([
            'model' => 1,
            'status' => 'draft',
            'other' => 'value'
        ]);

        $this->assertInstanceOf(ExampleModel::class, $result['model']);
        $this->assertEquals(ExampleStatus::DRAFT, $result['status']);
        $this->assertEquals('value', $result['other']);
    }

    #[Test]
    public function it_handles_missing_class_key()
    {
        $binder = new SubstituteBindings([
            'model' => []
        ]);

        $result = $binder->resolve(['model' => 'value']);

        $this->assertEquals('value', $result['model']);
    }

    #[Test]
    public function it_accepts_enum_instance_as_value()
    {
        $binder = new SubstituteBindings([
            'status' => ['class' => ExampleStatus::class]
        ]);

        $result = $binder->resolve(['status' => ExampleStatus::DRAFT]);

        $this->assertSame(ExampleStatus::DRAFT, $result['status']);
    }
}

enum ExampleStatus: string
{
    case DRAFT = 'draft';
    case PUBLISHED = 'published';
}

class ExampleModel implements UrlRoutable
{
    use SoftDeletes;

    public function getRouteKey()
    {
        return 1;
    }

    public function getRouteKeyName()
    {
        return 'id';
    }

    public function resolveRouteBinding($value, $field = null)
    {
        if ($field === 'slug' && $value === 'custom-slug') {
            return $this;
        }

        return $value == 1 ? $this : null;
    }

    public function resolveSoftDeletableRouteBinding($value, $field = null)
    {
        return $value == 2 ? $this : null;
    }

    public function resolveChildRouteBinding($childType, $value, $field)
    {
        // TODO: Implement resolveChildRouteBinding() method.
    }
}
