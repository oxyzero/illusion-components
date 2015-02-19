<?php

class Foo {
    public function give($amount)
    {
        return $amount;
    }
}

class Bar {
    protected $qux;

    public function __construct(\Foo $foo, $number = 1) {
        $this->set($foo->give($number));
    }

    public function get()
    {
        return $this->qux;
    }

    public function set($qux) {
        $this->qux = $qux;
    }
}

use Illusion\Container\Container;

class ContainerTest extends PHPUnit_Framework_TestCase
{
    private $c;

    public function setUp()
    {
        $this->c = new Container;
    }

    public function tearDown()
    {
        $this->c = new Container;
    }

    public function testOffsets()
    {
        $this->c['foo'] = 'bar';
        $this->assertInstanceOf('\Bar', $this->c['foo']);
        $this->assertTrue($this->c->has('foo'));
        unset($this->c['foo']);
        $this->assertFalse($this->c->has('foo'));
    }

    public function testDynamicallyCallingServices()
    {
        $this->c->foo = '\Foo';
        $this->c->bar = function($c) {
            return new \Bar($c->foo);
        };

        $this->assertTrue(isset($this->c->foo));
        $this->assertTrue(isset($this->c->bar));
        $this->assertInstanceOf('Foo', $this->c->foo);
        $this->assertInstanceOf('Bar', $this->c->bar);

        unset($this->c->foo, $this->c->bar);
        $this->assertFalse(isset($this->c->foo));
        $this->assertFalse(isset($this->c->bar));
    }

    public function testIfBindingExists()
    {
        $this->c->register('foo', 'Bar');
        $this->assertTrue($this->c->has('foo'));
    }

    public function testBindingValue()
    {
        $this->c->register('foo', 'Bar');

        $expect = ['value' => '\Bar', 'shared' => false];

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
        $this->c->register('Foo');
        $resolve = $this->c->resolve('Foo');

        $this->assertInstanceOf('Foo', $resolve);
    }

    public function testDirectResolve()
    {
        $this->c->register('foo', 'Foo');
        $fooInstance = $this->c->resolve('foo');
        $this->assertInstanceOf('Foo', $fooInstance);

        $this->c->register('bar', 'Bar');
        $barInstance = $this->c->resolve('bar');
        $this->assertInstanceOf('Bar', $barInstance);
    }

    public function testClosureResolve()
    {
        $this->c->register('foo', function() {
            return new Foo;
        });

        $fooResolve = $this->c->resolve('foo');

        $this->assertInstanceOf('Foo', $fooResolve);

        $this->c->register('bar', function() {
            $foo = new Foo;
            return new Bar($foo);
        });

        $barResolve = $this->c->resolve('bar');

        $this->assertInstanceOf('Bar', $barResolve);
    }

    public function testClosureResolveWithParameter()
    {
        $this->c->register('bar', function($container) {
            $container->register('foo', 'Foo');
            return new Bar($container->resolve('foo'));
        });

        $resolve = $this->c->resolve('bar');

        $this->assertInstanceOf('Bar', $resolve);
    }

    public function testSharedInstances()
    {
        $this->c->singleton('foo', '\Foo');
        $this->c->share('bar', '\Bar');

        $fooInstance  = $this->c->resolve('foo');
        $fooInstance2 = $this->c->resolve('foo');

        $barInstance  = $this->c->resolve('bar');
        $barInstance2 = $this->c->resolve('bar');

        $this->assertSame($fooInstance, $fooInstance2);
        $this->assertSame($barInstance, $barInstance2);
    }

    public function testDeleteSharedInstances()
    {
        $this->c->singleton('foo', '\Foo');
        $this->c->share('bar', '\Bar');

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
        $foo = new \Foo;
        $this->c->instance('foo', $foo);

        $resolve = $this->c->resolve('foo');
        $this->assertInstanceOf('Foo', $resolve);
    }

    public function testExtendingServices()
    {
        $this->c->register('bar', '\Bar');

        $this->c->extend('bar', function($bar, $c) {
            $bar->set(5);
            $c->register('foo', '\Foo');
            return $bar;
        });

        $resolve = $this->c->resolve('foo');
        $barGetValue = $this->c->bar->get();

        $this->assertInstanceOf('Foo', $resolve);
        $this->assertEquals(5, $barGetValue);
    }

    public function testPassingValuesThroughClosure()
    {
        $this->c->register('bar', function($number, $c) {
            $number *= 2;
            return new \Bar(new Foo, $number);
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
        $this->c->register('Foo');
        $this->c->protect('bar', function($value, $c) {
            return $c['Foo']->give(2) * $value;
        });

        $protected = $this->c->getProtected('bar', [ 2 ]);

        $this->assertEquals(4, $protected);
    }
}
