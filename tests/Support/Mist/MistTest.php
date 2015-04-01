<?php

use Tests\Support\Mist\Calc;
use Tests\Support\Mist\CalcContainer;
use Tests\Support\Mist\Calculator;

use Illusion\Support\Mist\Mist;

class MistTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        Mist::setContainer(new \Illusion\Container\Container);
        Mist::clearResolvedInstances();
    }

    public function testMist()
    {
        $result = Calc::sum(5)->subtract(2)->result();

        $this->assertEquals($result, 3);
    }

    public function testMistWithContainer()
    {
        $result = CalcContainer::sum(5)->subtract(2)->result();

        $this->assertEquals($result, 3);
    }
}
