<?php

namespace Aziraphale\RaspberryPiSetup\Stage;

use Aziraphale\RaspberryPiSetup\Util\Bailout;
use Aziraphale\RaspberryPiSetup\Util\StageCore;
use Aziraphale\RaspberryPiSetup\Util\StageInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;

class Etckeeper extends StageCore implements StageInterface
{
    private static $stageNumber      = 8;
    private static $stageName        = "Etckeeper Configuration";
    private static $stageDescription = "Sets up & configures etckeeper to that this Pi's `/etc/` directory will be tracked in version control.";

    public function __construct(InputInterface $input, OutputInterface $output, Bailout $bailout)
    {
        parent::__construct($input, $output, $bailout, self::$stageNumber, self::$stageName, self::$stageDescription);
    }

    public function run()
    {
        $this->output->writeln("This is run() of stage #{$this->getNumber()} “{$this->getName()}”!");

        /*
        ## What name to use for etckeeper's git branch?
        example="pi-mine"
        [ "$(hostname)" =~ ^pi- ] && example="$(hostname)"
        BRANCH_NAME=""
        while [[ ! $BRANCH_NAME =~ ^pi-[A-Za-z0-9_\-]+$ ]]; do
            echo "[$STAGE] Enter the name (starting with 'pi-') to use for this Pi's etckeeper branch, e.g. '$example'."
            read -p "[$STAGE] etckeeper git branch name: " BRANCH_NAME
        done
         */
        $this->output->writeln("Setting up etckeeper!");
        try {
            $this->newProcessTty("")->mustRun();
        } catch (ProcessFailedException $ex) {
            $this->bailout->writeln("")->bail();
        }
        try {
            // @todo Need to find existing branches to ensure that new branch name is unique
            $newBranchName = $this->askForString();
            $this->newProcessTty("")->mustRun();
        } catch (ProcessFailedException $ex) {
            $this->bailout->writeln("")->bail();
        }

        $this->output->writeln("");
        try {
            $this->newProcessTty("")->mustRun();
        } catch (ProcessFailedException $ex) {
            $this->bailout->writeln("")->bail();
        }

        $this->output->writeln("");
        try {
            $this->newProcessTty("")->mustRun();
        } catch (ProcessFailedException $ex) {
            $this->bailout->writeln("")->bail();
        }

        $this->output->writeln("Copying into place the etckeeper push hook...");
        try {
            $this->newProcessTty("cp ~pi/scripts/etckeeper-push/99push-pi-doorcam.sh /etc/etckeeper/commit.d/")->mustRun();
        } catch (ProcessFailedException $ex) {
            $this->bailout->writeln("")->bail();
        }
        /*
		echo "[$STAGE] Renaming etckeeper git branch and adding github as our origin remote..."
		pushd /etc && sudo git branch -m master "$BRANCH_NAME" || bailout "$LINENO: Failed to rename etckeeper git branch?!"
		sudo git remote add origin git@github.com:aziraphale/etc.git || bailout "$LINENO: Failed to add github as origin remote..."

		echo "[$STAGE] Creating etckeeper on-commit script to push changes to github..."
		{
			HOOK_SCRIPT_CONTENTS=<<<-EOFILE
			#!/bin/sh
			if [ "\$VCS" = git ] && [ -d .git ]; then
			        git push origin "$BRANCH_NAME"
			else
			        echo "PUSH_REMOTE not yet supported for \$VCS" >&2
			fi
			EOFILE
			echo "$HOOK_SCRIPT_CONTENTS" | sudo tee /etc/etckeeper/commit.d/99push-pi.sh
		} || bailout "$LINENO: Unable to write etckeeper on-commit script!"

		sudo chmod +x etckeeper/commit.d/99push-pi.sh
         */
    }
}
