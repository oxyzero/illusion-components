<?php

namespace Tests\Container\Services;

class Bar
{
    /**
     * Instance of Foo service.
     * @var object
     */
    protected $foo;

    /**
     * Some random value.
     * @var numeric
     */
    protected $number;

    /**
     * Bar constructor
     * @param \Tests\Container\Services\Foo $foo
     */
    public function __construct(\Tests\Container\Services\Foo $foo, $number = 1)
    {
        $this->foo = $foo;
        $this->number = $number;
    }

    /**
     * Set the number.
     * @param numeric $number
     */
    public function set($number)
    {
        $this->number = $this->foo->give($number);
    }

    /**
     * Get the number
     * @return numeric
     */
    public function get()
    {
        return $this->number;
    }
}
