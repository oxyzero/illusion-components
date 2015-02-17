<?php

class Foo {}

class Bar {
    public function __construct(Foo $foo) {}
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
        $this->assertEquals('bar', $this->c['foo']);
        $this->assertTrue($this->c->has('foo'));
        unset($this->c['foo']);
        $this->assertFalse($this->c->has('foo'));
    }

    public function testIfBindingExists()
    {
        $this->c->register('foo', 'Bar');
        $this->assertTrue($this->c->has('foo'));
        $this->assertEquals('Bar', $this->c->get('foo'));
    }

    public function testBindingValue()
    {
        $this->c->register('foo', 'Bar');
        $this->assertEquals('Bar', $this->c->get('foo'));
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
        $this->c->resolve('foo');
        $this->assertInstanceOf('Foo', $this->c->resolve('foo'));

        $this->c->register('bar', 'Bar');
        $this->c->resolve('bar');
        $this->assertInstanceOf('Bar', $this->c->resolve('bar'));
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

}
