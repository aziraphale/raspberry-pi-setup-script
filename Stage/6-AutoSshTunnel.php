<?php

namespace Aziraphale\RaspberryPiSetup\Stage;

use Aziraphale\RaspberryPiSetup\Util\Bailout;
use Aziraphale\RaspberryPiSetup\Util\StageCore;
use Aziraphale\RaspberryPiSetup\Util\StageInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class AutoSshTunnel extends StageCore implements StageInterface
{
    private static $stageNumber      = 6;
    private static $stageName        = "Auto-SSH Tunnel Setup";
    private static $stageDescription = "Sets up a reverse SSH tunnel (port 22000-ish on Hat forwarded to port 22 locally) managed by AutoSSH so that we can SSH into this Pi from our remote server, even if we don't know this Pi's IP address.";

    private $answerSshServiceHostname;

    private $sshServiceFilePattern = "/home/pi/scripts/init-scripts/ssh-tunnels/ag-ssh-tunnels.%s.service";
    private $sshServiceSystemdMatchPattern = "ag-ssh-tunnels*";

    public function __construct(InputInterface $input, OutputInterface $output, Bailout $bailout, \stdClass $config)
    {
        parent::__construct($input, $output, $bailout, $config, self::$stageNumber, self::$stageName, self::$stageDescription);
    }

    public function run()
    {
        $this->output->writeln("This is run() of stage #{$this->getNumber()} “{$this->getName()}”!");

        $testProc = new Process("sudo systemctl status " . escapeshellarg($this->sshServiceSystemdMatchPattern));
        $testProc->run();
        if ((string) $testProc->getOutput() === "") {
            // Not output from `systemctl status "ag-ssh-tunnels*"` indicates the service isn't yet installed
            $this->askSshTunnelServiceHostname();
            $sshTunnelHost = $this->answerSshServiceHostname;
            $serviceFile = sprintf($this->sshServiceFilePattern, $sshTunnelHost);

            if (!file_exists($serviceFile)) {
                // copy from base file
                $this->output->writeln("The specified SSH tunnel service file `$serviceFile` doesn't exist. That ".
                                       "file will be created by copying from the base service file, however it will ".
                                       "need to be modified to forward the correct port number.");
                if (!copy(sprintf($this->sshServiceFilePattern, 'base'), $serviceFile)) {
                    // failed to copy?!
                    $this
                        ->bailout
                        ->writeln("Failed to copy the base SSH tunnel service file to the specified filename...")
                        ->bail();
                }
                $this->output->writeln("When you press ENTER, vim will be opened with the service file loaded into ".
                                       "it. You must then set the correct forwarded port number, before saving and ".
                                       "exiting vim.");
                $this->pressEnterToContinue();

                try {
                    $this
                        ->newProcessTty("nano " . escapeshellarg($serviceFile))
                        ->mustRun();
                } catch (ProcessFailedException $ex) {
                    //$this->bailout->writeln("")->bail();
                }
            }

            // The service file ought to exist by now...
            // systemctl --system enable ~/scripts/init-scripts/pi-camera/ag-pi-camera.doorcam.service
            // systemctl start ag-pi-camera.doorcam.service
            $this->output->writeln("Installing the SSH Tunnels systemd service");
            try {
                $this
                    ->newProcessTty("sudo systemctl --system enable " . escapeshellarg($serviceFile))
                    ->mustRun();
            } catch (ProcessFailedException $ex) {
                $this
                    ->bailout
                    ->writeln("Failed to install the SSH Tunnels service...")
                    ->bail();
            }
            $this->output->writeln("Starting the SSH Tunnels systemd service");
            try {
                $this
                    ->newProcessTty("sudo systemctl start " . escapeshellarg(basename($serviceFile)))
                    ->mustRun();
            } catch (ProcessFailedException $ex) {
                $this
                    ->bailout
                    ->writeln("Failed to start the systemd service...")
                    ->bail();
            }
        } else {
            // Service exists already, so don't install it
            $this->output->writeln("The SSH Tunnels systemd service has already been installed, so we won't be ".
                                   "installing it again...");
        }

        $filterFilename = "/etc/rsyslog.d/30-filter.conf";
        if (file_exists($filterFilename) && stripos(file_get_contents($filterFilename), "autossh") !== false) {
            // Syslog code already exists...
            $this->output->writeln("Syslog appears to already be configured to filter out the autossh spam...");
        } else {
            $this->output->writeln("Reconfiguring syslog to filter out the spammy autossh messages...");
            try {
                $this
                    ->newProcessTty("echo -e '# AutoSSH is super-spammy! :(\n:syslogtag, contains, \"autossh\" ".
                                    "/var/log/autossh.log\n& stop' | sudo tee " . escapeshellarg($filterFilename))
                    ->mustRun();
            } catch (ProcessFailedException $ex) {
                $this
                    ->bailout
                    ->writeln("Failed to write to /etc/rsyslog.d/30-filter.conf!")
                    ->bail();
            }
        }

        $this->output->writeln("Reloading syslog to use the new config...");
        try {
            $this
                ->newProcessTty("sudo /etc/init.d/rsyslog force-reload")
                ->mustRun();
        } catch (ProcessFailedException $ex) {
            $this
                ->bailout
                ->writeln("Failed to reload rsyslog!")
                ->bail();
        }
    }
    public function askPreRunQuestions()
    {
        $this->askSshTunnelServiceHostname(static::ASKING_SRC_INIT);
    }

    public function askSshTunnelServiceHostname($askingSource = self::ASKING_SRC_PROCESS)
    {
        if (isset($this->answerSshServiceHostname)) {
            return;
        }

        $validation = function($value) {
            if ($value === "") {
                throw new \RuntimeException("You must enter a hostname for the SSH auto-tunnel service file.");
            }
            return $value;
        };

        if (!$this->getConfigValueIfPresent("SshTunnelServiceHostname")) {
            $this->output->writeln(
                "Please enter the name of this host as used to name the SSH auto-tunnel service file (e.g. pi-bedroom, pi0-doorcam, pi0-101, pi-kitchen, etc.)"
            );
        }
        $this->answerSshServiceHostname =
            $this->askForString("SshTunnelServiceHostname", "SSH auto-tunnel service hostname:", null, $validation);
    }
}
