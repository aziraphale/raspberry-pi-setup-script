<?php

namespace Aziraphale\RaspberryPiSetup\Stage;

use Aziraphale\RaspberryPiSetup\Util\Bailout;
use Aziraphale\RaspberryPiSetup\Util\StageCore;
use Aziraphale\RaspberryPiSetup\Util\StageInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;

class FishShell extends StageCore implements StageInterface
{
    private static $stageNumber      = 7;
    private static $stageName        = "Acquiring Fish Shell";
    private static $stageDescription = "Downloads the latest version of the Fish Shell, as the version in the Raspbian package repository tends to be fairly out-of-date.";

    public function __construct(InputInterface $input, OutputInterface $output, Bailout $bailout, \stdClass $config)
    {
        parent::__construct($input, $output, $bailout, $config, self::$stageNumber, self::$stageName, self::$stageDescription);
    }

    public function run()
    {
        $this->output->writeln("This is run() of stage #{$this->getNumber()} “{$this->getName()}”!");

        $this->output->writeln("Preparing to build fish-shell...");
        try {
            $fishDir = "/home/pi/apps/fish-shell";
            $this
                ->newProcessTty("git pull", $fishDir)
                ->mustRun();

            $this
                ->newProcessTty('git checkout `git tag -l --sort=version:refname | ".
                                "grep -P "^\d+\.\d+\.\d+" | tail -n1`', $fishDir)
                ->mustRun();

            $this
                ->newProcessTty("autoconf && ./configure && make && sudo make install", $fishDir)
                ->mustRun();
        } catch (ProcessFailedException $ex) {
            $this
                ->bailout
                ->writeln("Failed to build/install fish!")
                ->bail();
        }

        $this->output->writeln("Setting fish to be the default shell for both the 'pi' and 'root' users...");
        try {
            $this
                ->newProcessTty("echo /usr/local/bin/fish | sudo tee -a /etc/shells && ".
                                "sudo chsh -s /usr/local/bin/fish pi && ".
                                "sudo chsh -s /usr/local/bin/fish")
                ->mustRun();
        } catch (ProcessFailedException $ex) {
            $this
                ->bailout
                ->writeln("Failed to set fish as the default shell!")
                ->bail();
        }
    }
}
