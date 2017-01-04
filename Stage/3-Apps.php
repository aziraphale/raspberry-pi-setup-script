<?php

namespace Aziraphale\RaspberryPiSetup\Stage;

use Aziraphale\RaspberryPiSetup\Util\Bailout;
use Aziraphale\RaspberryPiSetup\Util\StageCore;
use Aziraphale\RaspberryPiSetup\Util\StageInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;

class Apps extends StageCore implements StageInterface
{
    private static $stageNumber      = 3;
    private static $stageName        = "Applications (Non-package Apps)";
    private static $stageDescription = "Downloads some required applications (which aren't available as Raspbian packages) into the ~/apps/ directory. Some may later require compilation.";

    public function __construct(InputInterface $input, OutputInterface $output, Bailout $bailout, \stdClass $config)
    {
        parent::__construct($input, $output, $bailout, $config, self::$stageNumber, self::$stageName, self::$stageDescription);
    }

    public function run()
    {
        $this->output->writeln("This is run() of stage #{$this->getNumber()} “{$this->getName()}”!");

        $this->output->writeln("Downloading lots of stuff into ~pi/apps");
        if (!@mkdir("/home/pi/apps") && !is_dir("/home/pi/apps")) {
            $this
                ->bailout
                ->writeln("Not even able to create ~pi/apps?!")
                ->bail();
        }

        if (!is_dir("/home/pi/apps/fish-shell")) {
            $this->output->writeln("Cloning fish-shell git repo...");
            try {
                $this
                    ->newProcessTty("git clone git@github.com:fish-shell/fish-shell.git ~pi/apps/fish-shell")
                    ->mustRun();
            } catch (ProcessFailedException $ex) {
                $this
                    ->bailout
                    ->writeln("Failed to git-clone fish-shell :(")
                    ->bail();
            }
        }

        if (!is_dir("/home/pi/apps/mjpg-streamer")) {
            $this->output->writeln("Cloning mjpg-streamer git repo & compiling it for Pi camera streaming...");
            try {
                $this
                    ->newProcessTty("git clone https://github.com/jacksonliam/mjpg-streamer.git ~pi/apps/mjpg-streamer")
                    ->mustRun();
                $this
                    ->newProcessTty("make && sudo make install", "/home/pi/apps/mjpg-streamer/mjpg-streamer-experimental")
                    ->mustRun();
            } catch (ProcessFailedException $ex) {
                $this
                    ->bailout
                    ->writeln("Failed to git-clone mjpg-streamer :(")
                    ->bail();
            }
        }

        //$this->output->writeln("Cloning quick2wire-gpio-admin...");
        //try {
        //    // @todo If it doesn't exist
        //    $this->newProcessTty(
        //        "git clone git://github.com/quick2wire/quick2wire-gpio-admin.git ~pi/apps/quick2wire-gpio-admin"
        //    )->mustRun();
        //} catch (ProcessFailedException $ex) {
        //    $this->bailout->writeln("Unable to clone quick2wire-gpio-admin...")->bail();
        //}

        //$this->output->writeln("Removing Raspbian's nodejs and installing newer node.js...");
        //try {
        //    // @todo If it doesn't exist
        //    $this->newProcessTty("sudo apt-get remove nodejs")->run();
        //    $this->newProcessTty(
        //        "wget http://node-arm.herokuapp.com/node_latest_armhf.deb && ".
        //        "sudo dpkg -i node_latest_armhf.deb",
        //        "~pi/apps"
        //    )->mustRun();
        //} catch (ProcessFailedException $ex) {
        //    $this->bailout->writeln("Unable to install node.js...")->bail();
        //}

        //$this->output->writeln("Installing WebIOPi...");
        //try {
        //    // @todo If it doesn't exist
        //    $this->newProcessTty(
        //        "wget http://netassist.dl.sourceforge.net/project/webiopi/WebIOPi-0.7.1.tar.gz && ".
        //        "tar xzf WebIOPi-0.7.1.tar.gz",
        //        "~pi/apps"
        //    )->mustRun();
        //} catch (ProcessFailedException $ex) {
        //    $this->bailout->writeln("Failed to download/extract WebIOPi... (or 'tar' might have just returned ".
        //                            "an unexpected exit code, which it does on occasion...)")->bail();
        //}
    }
}
