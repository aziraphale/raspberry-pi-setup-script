<?php

namespace Aziraphale\RaspberryPiSetup\Stage;

use Aziraphale\RaspberryPiSetup\Util\Bailout;
use Aziraphale\RaspberryPiSetup\Util\StageCore;
use Aziraphale\RaspberryPiSetup\Util\StageInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;

class PackageInstall extends StageCore implements StageInterface
{
    private static $stageNumber      = 2;
    private static $stageName        = "Package Install";
    private static $stageDescription = "Installs all the packages needed both for the rest of this setup process and for general running of the Pi.";

    public function __construct(InputInterface $input, OutputInterface $output, Bailout $bailout, \stdClass $config)
    {
        parent::__construct($input, $output, $bailout, $config, self::$stageNumber, self::$stageName, self::$stageDescription);
    }

    public function run()
    {
        $this->output->writeln("This is run() of stage #{$this->getNumber()} “{$this->getName()}”!");

        try {
            $packages = [
                "fish",
                "vim",
                "htop",
                "pv",
                "git",
                "autossh",
                "autoconf", "build-essential", "autotools-dev",
                "libncurses5-dev",
                "php5", "php5-curl",
                "sensord",
                "python-smbus",
                "i2c-tools",
                "screen",
                "etckeeper",
                "etherwake", "wakeonlan",
                "nmap",
                "bluez", "python-bluez",
                "ssed",
                "cmake", "libjpeg8-dev", // Needed for mjpg-streamer
            ];

            $this->output->writeln("Installing loads of required packages...");
            $this
                ->newProcessTty(
                    "sudo apt-get update && ".
                    "sudo apt-get -y upgrade && ".
                    "sudo apt-get -y install " . implode(" ", $packages)
                )
                ->mustRun();
        } catch (ProcessFailedException $ex) {
            $this
                ->bailout
                ->writeln("Failed to install required packages from apt... :(")
                ->bail();
        }
    }
}
