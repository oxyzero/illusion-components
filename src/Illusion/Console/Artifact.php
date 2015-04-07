<?php

namespace Illusion\Console;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Application as SymfonyApplication;
use Symfony\Component\Console\Command\Command as SymfonyCommand;

class Artifact extends SymfonyApplication implements ApplicationInterface
{
    private $commands = [];

    public function __construct($name, $version)
    {
        parent::__construct($name, $version);

        $this->setAutoExit(false);
        $this->setCatchException(false);
    }

    /**
     * Registers a new command.
     * @param  SymfonyCommand $command
     * @return \Symfony\Component\Console\Command\Command
     */
    public function register(SymfonyCommand $command)
    {
        return parent::add($command);
    }

}
