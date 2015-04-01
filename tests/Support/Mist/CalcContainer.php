<?php

namespace Tests\Support\Mist;

use Tests\Support\Mist\Calculator;

class CalcContainer extends \Illusion\Support\Mist\Mist
{
    protected static function mist()
    {
        return 'Tests\Support\Mist\Calculator';
    }
}
