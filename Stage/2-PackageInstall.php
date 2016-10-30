<?php

namespace Aziraphale\RaspberryPiSetup\Stage;

use Aziraphale\RaspberryPiSetup\Util\Bailout;
use Aziraphale\RaspberryPiSetup\Util\StageCore;
use Aziraphale\RaspberryPiSetup\Util\StageInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PackageInstall extends StageCore implements StageInterface
{
    private static $stageNumber      = 2;
    private static $stageName        = "Package Install";
    private static $stageDescription = "Installs all the packages needed both for the rest of this setup process and for general running of the Pi.";

    public function __construct(InputInterface $input, OutputInterface $output, Bailout $bailout, $listOnly)
    {
        parent::__construct($input, $output, $bailout, $listOnly, self::$stageNumber, self::$stageName, self::$stageDescription);
    }

    public function run()
    {
        $this->output->writeln("This is run() of stage #{$this->getNumber()} “{$this->getName()}”!");
        /*
        echo "[$STAGE] Installing loads of required packages..."
		sudo apt-get update && sudo apt-get upgrade && sudo apt-get install fish vim autossh autoconf build-essential libncurses5-dev htop pv git php5 php5-cli php5-curl autotools-dev sensord python-smbus i2c-tools screen etckeeper etherwake wakeonlan nmap bluez python-bluez ssed || bailout "$LINENO: Failed to install required packages from apt... :("
         */
    }
}
