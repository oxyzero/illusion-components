<?php

namespace Illusion\Console;

interface ApplicationInterface
{
    public function call($command, array $parameters = array());

    public function output();
}
