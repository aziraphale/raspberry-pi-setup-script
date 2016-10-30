<?php

namespace Aziraphale\RaspberryPiSetup\Stage;

use Aziraphale\RaspberryPiSetup\Util\Bailout;
use Aziraphale\RaspberryPiSetup\Util\StageCore;
use Aziraphale\RaspberryPiSetup\Util\StageInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Apps extends StageCore implements StageInterface
{
    private static $stageNumber      = 3;
    private static $stageName        = "Applications (Non-package Apps)";
    private static $stageDescription = "Downloads some required applications (which aren't available as Raspbian packages) into the ~/apps/ directory. Some may later require compilation.";

    public function __construct(InputInterface $input, OutputInterface $output, Bailout $bailout)
    {
        parent::__construct($input, $output, $bailout, self::$stageNumber, self::$stageName, self::$stageDescription);
    }

    public function run()
    {
        $this->output->writeln("This is run() of stage #{$this->getNumber()} “{$this->getName()}”!");
        /*
        echo "[$STAGE] Downloading lots of stuff into ~pi/apps"
		mkdir ~pi/apps && cd ~pi/apps || bailout "$LINENO: Not even able to create ~pi/apps?!"

		echo "[$STAGE] Cloning fish-shell git repo..."
		git clone git@github.com:fish-shell/fish-shell.git fish-shell || bailout "$LINENO: Failed to git-clone fish-shell :("

		echo "[$STAGE] Cloning quick2wire-gpio-admin..."
		git clone git://github.com/quick2wire/quick2wire-gpio-admin.git quick2wire-gpio-admin || bailout "$LINENO: Unable to clone quick2wire-gpio-admin..."

        echo "[$STAGE] Removing Raspbian's nodejs and installing newer node.js..."
        sudo apt-get remove nodejs
        wget http://node-arm.herokuapp.com/node_latest_armhf.deb && sudo dpkg -i node_latest_armhf.deb || bailout "$LINENO: Unable to install node.js..."

		echo "[$STAGE] Installing WebIOPi..."
		wget http://netassist.dl.sourceforge.net/project/webiopi/WebIOPi-0.7.1.tar.gz && tar xzf WebIOPi-0.7.1.tar.gz || bailout "$LINENO: Failed to download/extract WebIOPi... (or 'tar' might have just returned an unexpected exit code, which it does on occasion...)"
         */
    }
}
