<?php

namespace Tests\Container\Services;

class Foo
{
    /**
     * Gives back a value.
     * @param mixed $value
     */
    public function give($value)
    {
        return $value;
    }
}
