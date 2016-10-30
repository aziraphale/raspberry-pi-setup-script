<?php

namespace Aziraphale\RaspberryPiSetup\Stage;

use Aziraphale\RaspberryPiSetup\Util\Bailout;
use Aziraphale\RaspberryPiSetup\Util\StageCore;
use Aziraphale\RaspberryPiSetup\Util\StageInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FishShell extends StageCore implements StageInterface
{
    private static $stageNumber      = 7;
    private static $stageName        = "Acquiring Fish Shell";
    private static $stageDescription = "Downloads the latest version of the Fish Shell, as the version in the Raspbian package repository tends to be fairly out-of-date.";

    public function __construct(InputInterface $input, OutputInterface $output, Bailout $bailout, $listOnly)
    {
        parent::__construct($input, $output, $bailout, $listOnly, self::$stageNumber, self::$stageName, self::$stageDescription);
    }

    public function run()
    {
        $this->output->writeln("This is run() of stage #{$this->getNumber()} “{$this->getName()}”!");
        /*
        echo "[$STAGE] Building Fish-shell..."
		cd ~pi/apps/fish-shell/ && git tag -l || bailout "$LINENO: Failed to simply cd to the fish-shell WC and list tags?!"

		echo "[$STAGE] A new shell will now be started so that you can \`git checkout\` the latest stable tag from the above tag list. Hit Ctrl-d when finished to return to this script and start the build process. Example command: \`git checkout 2.2.0\`"
		bash || bailout "$LINENO: Invocation of bash subshell returned error exit status!"

		autoconf && ./configure && make || bailout "$LINENO: Failed to build fish-shell :("

		echo "[$STAGE] Installing fish and setting it as the default shell for pi and root users..."
		sudo make install && (echo /usr/local/bin/fish | sudo tee -a /etc/shells && sudo chsh -s /usr/local/bin/fish pi && sudo chsh -s /usr/local/bin/fish) || bailout "$LINENO: Failed to install fish and/or set it as the default shell..."
         */
    }
}
