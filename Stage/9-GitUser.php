<?php

namespace Aziraphale\RaspberryPiSetup\Stage;

use Aziraphale\RaspberryPiSetup\Util\Bailout;
use Aziraphale\RaspberryPiSetup\Util\StageCore;
use Aziraphale\RaspberryPiSetup\Util\StageInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GitUser extends StageCore implements StageInterface
{
    private static $stageNumber      = 9;
    private static $stageName        = "Git User Details";
    private static $stageDescription = "Configures the global git username and email address so that git doesn't annoyingly prompt for those in future.";

    public function __construct(InputInterface $input, OutputInterface $output, Bailout $bailout)
    {
        parent::__construct($input, $output, $bailout, self::$stageNumber, self::$stageName, self::$stageDescription);
    }

    public function run()
    {
        $this->output->writeln("This is run() of stage #{$this->getNumber()} “{$this->getName()}”!");
        /*
        echo "[$STAGE] Setting git global configs..."
		cd ~pi && git config --global user.email "andrew@lorddeath.net" && git config --global user.name "Andrew Gillard" && git config --global push.default simple || bailout "$LINENO: Failed to set git config values..."
         */
    }
}
