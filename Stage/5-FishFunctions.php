<?php

namespace Aziraphale\RaspberryPiSetup\Stage;

use Aziraphale\RaspberryPiSetup\Util\Bailout;
use Aziraphale\RaspberryPiSetup\Util\StageCore;
use Aziraphale\RaspberryPiSetup\Util\StageInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FishFunctions extends StageCore implements StageInterface
{
    private static $stageNumber      = 5;
    private static $stageName        = "Fish Functions Installation";
    private static $stageDescription = "Installs a load of fish (shell) functions for use by the 'pi' user.";

    public function __construct(InputInterface $input, OutputInterface $output, Bailout $bailout)
    {
        parent::__construct($input, $output, $bailout, self::$stageNumber, self::$stageName, self::$stageDescription);
    }

    public function run()
    {
        $this->output->writeln("This is run() of stage #{$this->getNumber()} “{$this->getName()}”!");
        /*
        echo "[$STAGE] Symlinking fish function files into our fish config directory..."
		mkdir -p ~pi/.config/fish/functions && pushd ~pi/.config/fish/functions && ln -s ~pi/scripts/fish/functions/* . && rm is.hat.fish && popd || bailout "$LINENO: Failed to symlink fish function files..."
         */
    }
}
