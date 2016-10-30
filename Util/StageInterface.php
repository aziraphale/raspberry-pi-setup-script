<?php

namespace Aziraphale\RaspberryPiSetup\Util;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

interface StageInterface
{
    public function __construct(InputInterface $input, OutputInterface $output, Bailout $bailout, $listOnly);
    public function getNumber();
    public function getName();
    public function getDescription();
    public function askPreRunQuestions();
    public function run();
}
