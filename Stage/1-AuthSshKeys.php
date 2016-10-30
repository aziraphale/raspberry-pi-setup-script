<?php

namespace Aziraphale\RaspberryPiSetup\Stage;

use Aziraphale\RaspberryPiSetup\Util\Bailout;
use Aziraphale\RaspberryPiSetup\Util\StageCore;
use Aziraphale\RaspberryPiSetup\Util\StageInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;

class AuthSshKeys extends StageCore implements StageInterface
{
    private static $stageNumber      = 1;
    private static $stageName        = "Auth & SSH Keys";
    private static $stageDescription = "Sets the 'pi' user password & installs authorised SSH public keys for SSH keypair auth.";

    public function __construct(InputInterface $input, OutputInterface $output, Bailout $bailout)
    {
        parent::__construct($input, $output, $bailout, self::$stageNumber, self::$stageName, self::$stageDescription);
    }

    public function run()
    {
        $this->output->writeln("This is run() of stage #{$this->getNumber()} “{$this->getName()}”!");

        $this->output->writeln("Pulling .ssh for Pi installs from Hex...");
        try {
            $this->newProcessTty("scp -r andrew@hex.lorddeath.net:/mnt/backups/RPi/_setup/.ssh ~pi/")->mustRun();
        } catch (ProcessFailedException $ex) {
            $this->bailout->writeln("Unable to fetch required .ssh dir from Hex!")->bail();
        }

        $this->newProcessTty("chmod 600 ~pi/.ssh/id_rsa")->mustRun();

        try {
            $this->output->writeln("Creating symlinks of everything in ~pi/.ssh/ in ~root/.ssh/...");
            $this->newProcessTty("sudo mkdir /root/.ssh && sudo ln ~pi/.ssh/* /root/.ssh/")->mustRun();
        } catch (ProcessFailedException $ex) {
            $this->bailout->writeln("Failed to symlink ~pi/.ssh/ files into ~root/.ssh")->bail();
        }
    }
}
