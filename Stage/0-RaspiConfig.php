<?php

namespace Aziraphale\RaspberryPiSetup\Stage;

use Aziraphale\RaspberryPiSetup\Util\Bailout;
use Aziraphale\RaspberryPiSetup\Util\StageCore;
use Aziraphale\RaspberryPiSetup\Util\StageInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RaspiConfig extends StageCore implements StageInterface
{
    private static $stageNumber      = 0;
    private static $stageName        = "Run Raspberry Pi Config";
    private static $stageDescription = "Runs the `raspi-config` utility to allow for various settings to be configured.";

    public function __construct(InputInterface $input, OutputInterface $output, Bailout $bailout, $listOnly)
    {
        parent::__construct($input, $output, $bailout, $listOnly, self::$stageNumber, self::$stageName, self::$stageDescription);
    }

    public function run()
    {
        $this->output->writeln("This is run() of stage #{$this->getNumber()} “{$this->getName()}”!");
        // TODO: Implement run() method.
        /*
        echo "[$STAGE] First things first, we need to run the raspi-config program and configure things within as apppropriate..."
		echo "[$STAGE] Press [ENTER] to proceed..."
		read x
		sudo raspi-config

		# Write "1" to `.pi-setup-state`
		echo "1" > "$STATE_FILE"
		echo "[$STAGE] NOTE: the Pi must now reboot. When finished rebooting, run this script again for it to continue where it left off."
		echo "[$STAGE] Press [ENTER] to continue..."
		read x
		sudo reboot
         */
    }
}
