<?php

namespace Aziraphale\RaspberryPiSetup\Stage;

use Aziraphale\RaspberryPiSetup\Util\Bailout;
use Aziraphale\RaspberryPiSetup\Util\StageCore;
use Aziraphale\RaspberryPiSetup\Util\StageInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;

class GitUser extends StageCore implements StageInterface
{
    private static $stageNumber      = 8;
    private static $stageName        = "Git User Details";
    private static $stageDescription = "Configures the global git username and email address so that git doesn't annoyingly prompt for those in future.";

    public function __construct(InputInterface $input, OutputInterface $output, Bailout $bailout, \stdClass $config)
    {
        parent::__construct($input, $output, $bailout, $config, self::$stageNumber, self::$stageName, self::$stageDescription);
    }

    public function run()
    {
        $this->output->writeln("This is run() of stage #{$this->getNumber()} “{$this->getName()}”!");

        $this->output->writeln("Configuring git user name & email address...");
        try {
            $this
                ->newProcessTty('git config --global user.name "Andrew Gillard"')
                ->mustRun();

            $this
                ->newProcessTty('git config --global user.email "andrew@lorddeath.net"')
                ->mustRun();
        } catch (ProcessFailedException $ex) {
            $this->bailout->writeln("")->bail();
        }
    }
}
