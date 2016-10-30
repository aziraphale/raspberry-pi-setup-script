<?php

namespace Aziraphale\RaspberryPiSetup\Stage;

use Aziraphale\RaspberryPiSetup\Util\Bailout;
use Aziraphale\RaspberryPiSetup\Util\StageCore;
use Aziraphale\RaspberryPiSetup\Util\StageInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Scripts extends StageCore implements StageInterface
{
    private static $stageNumber      = 4;
    private static $stageName        = "Scripts Downloading";
    private static $stageDescription = "Clones our 'scripts' git repository into ~/scripts/ so that all of the scripts we need for general Pi operation are available.";

    public function __construct(InputInterface $input, OutputInterface $output, Bailout $bailout, $listOnly)
    {
        parent::__construct($input, $output, $bailout, $listOnly, self::$stageNumber, self::$stageName, self::$stageDescription);
    }

    public function run()
    {
        $this->output->writeln("This is run() of stage #{$this->getNumber()} “{$this->getName()}”!");
        /*
        cd ~pi || bailout "$LINENO: Failed to cd to ~pi?!"

		echo "[$STAGE] Cloning our linux-scripts repo into ~pi/scripts..."
		git clone git@bitbucket.org:lorddeath/linux-scripts.git scripts || bailout "$LINENO: Failed to clone linux-scripts..."
         */
    }
}
