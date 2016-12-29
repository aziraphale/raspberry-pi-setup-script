<?php

namespace Aziraphale\RaspberryPiSetup\Stage;

use Aziraphale\RaspberryPiSetup\Util\Bailout;
use Aziraphale\RaspberryPiSetup\Util\StageCore;
use Aziraphale\RaspberryPiSetup\Util\StageInterface;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class RaspiConfig extends StageCore implements StageInterface
{
    private static $stageNumber      = 0;
    private static $stageName        = "Run Raspberry Pi Config";
    private static $stageDescription = "Runs the `raspi-config` utility to allow for various settings to be configured.";

    public function __construct(InputInterface $input, OutputInterface $output, Bailout $bailout, \stdClass $config)
    {
        parent::__construct($input, $output, $bailout, $config, self::$stageNumber, self::$stageName, self::$stageDescription);
    }

    public function run()
    {
        $this->output->writeln("First things first, we need to run the <hilight>raspi-config</hilight> program ".
                               "and configure things within as appropriate...");
        $this->output->writeln("Please <hilight>DO NOT REBOOT</hilight> after configuring this Pi. If you do ".
                               "choose to reboot, please run this script again after the reboot to resume setup.");

        $this->pressEnterToContinue();

        $this
            ->newProcessTty('sudo raspi-config')
            ->mustRun();
    }
}
