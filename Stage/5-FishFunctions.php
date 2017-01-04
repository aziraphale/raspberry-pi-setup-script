<?php

namespace Aziraphale\RaspberryPiSetup\Stage;

use Aziraphale\RaspberryPiSetup\Util\Bailout;
use Aziraphale\RaspberryPiSetup\Util\StageCore;
use Aziraphale\RaspberryPiSetup\Util\StageInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;

class FishFunctions extends StageCore implements StageInterface
{
    private static $stageNumber      = 5;
    private static $stageName        = "Fish Functions Installation";
    private static $stageDescription = "Installs a load of fish (shell) functions for use by the 'pi' user.";

    public function __construct(InputInterface $input, OutputInterface $output, Bailout $bailout, \stdClass $config)
    {
        parent::__construct($input, $output, $bailout, $config, self::$stageNumber, self::$stageName, self::$stageDescription);
    }

    public function run()
    {
        $this->output->writeln("This is run() of stage #{$this->getNumber()} “{$this->getName()}”!");

        if (!is_dir("/home/pi/.config/fish/functions") || count(glob("/home/pi/.config/fish/functions/*.fish")) === 0) {
            $this->output->writeln("Symlinking fish function files into our fish config directory...");
            try {
                $this
                    ->newProcessTty("mkdir -p ~pi/.config/fish/functions")
                    ->mustRun()
                ;
                $this
                    ->newProcessTty("ln -s ~pi/scripts/fish/functions/* .", "/home/pi/.config/fish/functions")
                    ->mustRun()
                ;
                $this
                    ->newProcessTty("rm /home/pi/.config/fish/functions/is.hat.fish")
                    ->run()
                ;
            } catch (ProcessFailedException $ex) {
                $this
                    ->bailout
                    ->writeln("Failed to symlink fish function files...")
                    ->bail();
            }
        }
    }
}
