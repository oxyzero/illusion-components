<?php

// Test classes
use Tests\Container\Services\Foo;
use Tests\Container\Services\Bar;

use Illusion\Container\Container;

class ContainerTest extends PHPUnit_Framework_TestCase
{
    private $c;
    private $barNamespace;
    private $fooNamespace;

    public function setUp()
    {
        $this->c = new Container;
        $this->barNamespace = 'Tests\Container\Services\Bar';
        $this->fooNamespace = 'Tests\Container\Services\Foo';
    }

    public function tearDown()
    {
        $this->c = new Container;
    }

    public function testOffsets()
    {
        $this->c['foo'] = $this->barNamespace;
        $this->assertInstanceOf($this->barNamespace, $this->c['foo']);
        $this->assertTrue($this->c->has('foo'));
        unset($this->c['foo']);
        $this->assertFalse($this->c->has('foo'));
    }

    public function testDynamicallyCallingServices()
    {
        $this->c->foo = $this->fooNamespace;
        $this->c->bar = function($c) {
            return new Bar($c->foo);
        };

        $this->assertTrue(isset($this->c->foo));
        $this->assertTrue(isset($this->c->bar));
        $this->assertInstanceOf($this->fooNamespace, $this->c->foo);
        $this->assertInstanceOf($this->barNamespace, $this->c->bar);

        unset($this->c->foo, $this->c->bar);
        $this->assertFalse(isset($this->c->foo));
        $this->assertFalse(isset($this->c->bar));
    }

    public function testIfBindingExists()
    {
        $this->c->register('foo', $this->barNamespace);
        $this->assertTrue($this->c->has('foo'));
    }

    public function testBindingValue()
    {
        $this->c->register('foo', $this->barNamespace);

        $expect = ['value' => '\\' . $this->barNamespace, 'shared' => false];

        $binding = $this->c->get('foo');

        $this->assertEquals($expect, $binding);
    }

    public function testReturningNullWhenBindingDoesnExist()
    {
        $binding = $this->c->get('qux');
        $this->assertNull($binding);
    }

    public function testResolveBindingWithNoValue()
    {
        $this->c->register($this->fooNamespace);
        $resolve = $this->c->resolve($this->fooNamespace);

        $this->assertInstanceOf($this->fooNamespace, $resolve);
    }

    public function testDirectResolve()
    {
        $this->c->register('foo', $this->fooNamespace);
        $fooInstance = $this->c->resolve('foo');
        $this->assertInstanceOf($this->fooNamespace, $fooInstance);

        $this->c->register('bar', $this->barNamespace);
        $barInstance = $this->c->resolve('bar');
        $this->assertInstanceOf($this->barNamespace, $barInstance);
    }

    public function testClosureResolve()
    {
        $this->c->register('foo', function() {
            return new Foo;
        });

        $fooResolve = $this->c->resolve('foo');

        $this->assertInstanceOf($this->fooNamespace, $fooResolve);

        $this->c->register('bar', function($c) {
            return new Bar($c->foo);
        });

        $barResolve = $this->c->resolve('bar');

        $this->assertInstanceOf($this->barNamespace, $barResolve);
    }

    public function testClosureResolveWithParameter()
    {
        $this->c->register('bar', function($container) {
            $container->register('foo', $this->fooNamespace);
            return new Bar($container->resolve('foo'));
        });

        $resolve = $this->c->resolve('bar');

        $this->assertInstanceOf($this->barNamespace, $resolve);
    }

    public function testSharedInstances()
    {
        $this->c->singleton('foo', $this->fooNamespace);
        $this->c->share('bar', $this->barNamespace);

        $fooInstance  = $this->c->resolve('foo');
        $fooInstance2 = $this->c->resolve('foo');

        $barInstance  = $this->c->resolve('bar');
        $barInstance2 = $this->c->resolve('bar');

        $this->assertSame($fooInstance, $fooInstance2);
        $this->assertSame($barInstance, $barInstance2);
    }

    public function testDeleteSharedInstances()
    {
        $this->c->singleton('foo', $this->fooNamespace);
        $this->c->share('bar', $this->barNamespace);

        $fooInstance  = $this->c->resolve('foo');
        $this->c->deleteInstance('foo');
        $fooInstance2 = $this->c->resolve('foo');

        $barInstance  = $this->c->resolve('bar');
        $this->c->deleteInstance('bar');
        $barInstance2 = $this->c->resolve('bar');

        $this->assertNotSame($fooInstance, $fooInstance2);
        $this->assertNotSame($barInstance, $barInstance2);
    }

    public function testBindAlreadyInstantiatedObject()
    {
        $foo = new Foo;
        $this->c->instance('foo', $foo);

        $resolve = $this->c->resolve('foo');
        $this->assertInstanceOf($this->fooNamespace, $resolve);
    }

    public function testExtendingServices()
    {
        $this->c->register('bar', $this->barNamespace);

        $this->c->extend('bar', function($bar, $c) {
            $bar->set(5);
            $c->register('foo', $this->fooNamespace);
            return $bar;
        });

        $resolve = $this->c->resolve('foo');
        $barGetValue = $this->c->bar->get();

        $this->assertInstanceOf($this->fooNamespace, $resolve);
        $this->assertEquals(5, $barGetValue);
    }

    public function testPassingValuesThroughClosure()
    {
        $this->c->register('bar', function($number, $c) {
            $number *= 2;
            return new Bar(new Foo, $number);
        });

        $barGetValue = $this->c->resolve('bar', [2])->get();

        $this->assertEquals(4, $barGetValue);
    }

    public function testProtectedParameters()
    {
        $this->c->protect('Foo', function() {
            return 'foo';
        });

        $protected = $this->c->getProtected('Foo');

        $this->assertEquals('foo', $protected);
    }

    public function testProtectedParametersWithArguments()
    {
        $this->c->register($this->fooNamespace);
        $this->c->protect('bar', function($value, $c) {
            return $c[$this->fooNamespace]->give(2) * $value;
        });

        $protected = $this->c->getProtected('bar', [ 2 ]);

        $this->assertEquals(4, $protected);
    }

    public function testResolvingMethods()
    {
        $method = $this->c->method($this->barNamespace . '@get');

        $this->assertEquals(1, $method);
    }

    public function testResolvingMethodsWithParameters()
    {
        $method = $this->c->method($this->fooNamespace . '@give', [5]);

        $this->assertEquals(5, $method);
    }
}
