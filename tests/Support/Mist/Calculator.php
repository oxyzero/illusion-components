<?php

namespace Tests\Support\Mist;

class Calculator
{
    protected $result;

    public function __construct()
    {
        $this->result = 0;
    }

    public function sum($number)
    {
        $this->result += $number;

        return $this;
    }

    public function subtract($number)
    {
        $this->result -= $number;

        return $this;
    }

    public function multiply($number)
    {
        $this->result *= $number;

        return $this;
    }

    public function divide($number)
    {
        $this->result /= $number;

        return $this;
    }

    public function result()
    {
        return $this->result;
    }
}
