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

    public function testIfBindingExists()
    {
        $this->c->register('foo', 'Bar');
        $this->assertTrue($this->c->has('foo'));
    }

    public function testBindingValue()
    {
        $this->c->register('foo', 'Bar');
        $this->assertEquals(['value' => '\Bar', 'shared' => false], $this->c->get('foo'));
    }

    public function testReturningNullWhenBindingDoesnExist()
    {
        $this->assertNull($this->c->get('qux'));
    }

    public function testResolveBindingWithNoValue()
    {
        $this->c->register('Foo');
        $this->assertInstanceOf('Foo', $this->c->resolve('Foo'));
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

        $this->assertInstanceOf('Foo', $this->c->resolve('foo'));

        $this->c->register('bar', function() {
            $foo = new Foo;
            return new Bar($foo);
        });

        $this->assertInstanceOf('Bar', $this->c->resolve('bar'));
    }

    public function testClosureResolveWithParameter()
    {
        $this->c->register('bar', function($container) {
            $container->register('foo', 'Foo');
            return new Bar($container->resolve('foo'));
        });

        $this->assertInstanceOf('Bar', $this->c->resolve('bar'));
    }

    public function testSharedInstances()
    {
        $this->c->singleton('foo', '\Foo');
        $this->c->share('bar', '\Bar');

        $fooInstance  = $this->c->resolve('foo');
        $fooInstance2 = $this->c->resolve('foo');

        $barInstance  = $this->c->resolve('bar');
        $barInstance2 = $this->c->resolve('bar');

        $this->assertTrue($fooInstance === $fooInstance2);
        $this->assertTrue($barInstance === $barInstance2);
    }

    public function testBindAlreadyInstantiatedObject()
    {
        $foo = new \Foo;
        $this->c->instance('foo', $foo);
        $this->assertInstanceOf('Foo', $this->c->resolve('foo'));
    }

    public function testExtendingServices()
    {
        $this->c->register('bar', '\Bar');

        $this->c->resolve('bar');

        $this->c->extend('bar', function($bar, $c) {
            $bar->set(5);
            $c->register('foo', '\Foo');
            return $bar;
        });

        $this->assertInstanceOf('Foo', $this->c->resolve('foo'));
        $this->assertEquals(5, $this->c['bar']->get());
    }

    public function testPassingValuesThroughClosure()
    {
        $this->c->register('bar', function($number, $c) {
            $number *= 2;
            return new \Bar(new Foo, $number);
        });

        $this->assertEquals(4, $this->c->resolve('bar', [2])->get());
    }

}
