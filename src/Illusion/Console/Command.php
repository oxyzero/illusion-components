<?php

namespace Illusion\Console;

use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class Command extends SymfonyCommand
{
    protected $input;

    protected $output;

    protected $name;

    protected $description;

    public function __construct()
    {
        parent::__construct($this->name);

        $this->setDescription($this->description);

        $this->setParameters();
    }

    protected function setParameters()
    {
        foreach ($this->getArguments() as $args) {
            call_user_func_array([$this, 'addArgument'], $args);
        }

        foreach ($this->getOptions() as $opts) {
            call_user_func_array([$this, 'addOption'], $opts);
        }
    }

    public function run(InputInterface $input, OutputInterface $output)
    {
        $this->input  = $input;
        $this->output = $output;

        return parent::run($input, $output);
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $method = method_exists($this, 'handle') ? 'handle' : 'fire';

    }
}
