<?php

namespace Aziraphale\RaspberryPiSetup\Stage;

use Aziraphale\RaspberryPiSetup\Util\Bailout;
use Aziraphale\RaspberryPiSetup\Util\StageCore;
use Aziraphale\RaspberryPiSetup\Util\StageInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class Scripts extends StageCore implements StageInterface
{
    private static $stageNumber      = 4;
    private static $stageName        = "Scripts Downloading";
    private static $stageDescription = "Clones our 'scripts' git repository into ~/scripts/ so that all of the scripts we need for general Pi operation are available.";

    private $answerInstallCameraService;
    private $answerCameraServiceHostname;

    private $piCameraServiceFilePattern = "/home/pi/scripts/init-scripts/pi-camera/ag-pi-camera.%s.service";
    private $piCameraServiceSystemdMatchPattern = "ag-pi-camera*";

    public function __construct(InputInterface $input, OutputInterface $output, Bailout $bailout)
    {
        parent::__construct($input, $output, $bailout, self::$stageNumber, self::$stageName, self::$stageDescription);
    }

    public function run()
    {
        $this->output->writeln("This is run() of stage #{$this->getNumber()} “{$this->getName()}”!");

        if (!is_dir("/home/pi/scripts")) {
            $this->output->writeln("Cloning our linux-scripts repo into ~pi/scripts...");
            try {
                $this
                    ->newProcessTty("git clone git@bitbucket.org:lorddeath/linux-scripts.git ~pi/scripts")
                    ->mustRun();
            } catch (ProcessFailedException $ex) {
                $this
                    ->bailout
                    ->writeln("Failed to clone linux-scripts...")
                    ->bail();
            }
        } else {
            $this->output->writeln("linux-scripts repo has already been cloned...");
        }

        $testProc = new Process("systemctl status " . escapeshellarg($this->piCameraServiceSystemdMatchPattern));
        $testProc->run();
        if ((string) $testProc->getOutput() === "") {
            // Not output from `systemctl status "ag-pi-camera*"` indicates the service isn't yet installed
            $this->askInstallCameraService();
            if ($this->answerInstallCameraService) {
                $this->askCameraServiceHostname();
                $cameraHost = $this->answerCameraServiceHostname;
                $serviceFile = sprintf($this->piCameraServiceFilePattern, $cameraHost);

                if (!file_exists($serviceFile)) {
                    // copy from base file
                    $this->output->writeln("The specified Pi Camera service file `$serviceFile` doesn't exist, ".
                                           "so it will be created by copying from the base file.");
                    if (!copy(sprintf($this->piCameraServiceFilePattern, 'base'), $serviceFile)) {
                        // failed to copy?!
                        $this
                            ->bailout
                            ->writeln("Failed to copy the base Pi Camera service file to the specified filename...")
                            ->bail();
                    }
                }

                // The service file ought to exist by now...
                // systemctl --system enable ~/scripts/init-scripts/pi-camera/ag-pi-camera.doorcam.service
                // systemctl start ag-pi-camera.doorcam.service
                $this->output->writeln("Installing the Pi Camera systemd service");
                try {
                    $this
                        ->newProcessTty("systemctl --system enable " . escapeshellarg($serviceFile))
                        ->mustRun();
                } catch (ProcessFailedException $ex) {
                    $this
                        ->bailout
                        ->writeln("Failed to install the systemd service...")
                        ->bail();
                }
                $this->output->writeln("Starting the Pi Camera systemd service");
                try {
                    $this
                        ->newProcessTty("systemctl start " . escapeshellarg(basename($serviceFile)))
                        ->mustRun();
                } catch (ProcessFailedException $ex) {
                    $this
                        ->bailout
                        ->writeln("Failed to start the systemd service...")
                        ->bail();
                }
            }
        }
    }

    public function askPreRunQuestions()
    {
        $this->askInstallCameraService(static::ASKING_SRC_INIT);
        $this->askCameraServiceHostname(static::ASKING_SRC_INIT);
    }

    public function askInstallCameraService($askingSource = self::ASKING_SRC_PROCESS)
    {
        if (isset($this->answerInstallCameraService)) {
            return;
        }
        $this->answerInstallCameraService =
            $this->askForConfirmation(
                "InstallCameraService",
                "Do you want to install the Pi Camera service? [y/N]",
                false
            );
    }

    public function askCameraServiceHostname($askingSource = self::ASKING_SRC_PROCESS)
    {
        if (isset($this->answerCameraServiceHostname)) {
            return;
        }
        if ($this->answerInstallCameraService !== true) {
            // Only ask this if we're actually installing the service
            return;
        }

        $validation = function($value) {
            if ($value === "") {
                throw new \RuntimeException("You must enter a hostname for the Pi Camera service file.");
            }
            return $value;
        };

        $this->output->writeln("Please enter the name of this host as used to name the Pi Camera service files (e.g. bedroom, doorcam, doormat, kitchen, etc.)");
        $this->answerCameraServiceHostname =
            $this->askForString("CameraServiceHostname", "Pi Camera service hostname:", null, $validation);
    }
}
