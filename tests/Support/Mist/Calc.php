<?php

namespace Tests\Support\Mist;

use Tests\Support\Mist\Calculator;

class Calc extends \Illusion\Support\Mist\Mist
{
    protected static function mist()
    {
        return new Calculator;
    }
}
