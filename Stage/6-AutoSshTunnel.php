<?php

namespace Aziraphale\RaspberryPiSetup\Stage;

use Aziraphale\RaspberryPiSetup\Util\Bailout;
use Aziraphale\RaspberryPiSetup\Util\StageCore;
use Aziraphale\RaspberryPiSetup\Util\StageInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AutoSshTunnel extends StageCore implements StageInterface
{
    private static $stageNumber      = 6;
    private static $stageName        = "Auto-SSH Tunnel Setup";
    private static $stageDescription = "Sets up a reverse SSH tunnel (port 22000-ish on Hat forwarded to port 22 locally) managed by AutoSSH so that we can SSH into this Pi from our remote server, even if we don't know this Pi's IP address.";

    public function __construct(InputInterface $input, OutputInterface $output, Bailout $bailout)
    {
        parent::__construct($input, $output, $bailout, self::$stageNumber, self::$stageName, self::$stageDescription);
    }

    public function run()
    {
        $this->output->writeln("This is run() of stage #{$this->getNumber()} “{$this->getName()}”!");
        /*
        echo "[$STAGE] Copying the SSH-Tunnels init script into place..."
		sudo cp ~pi/scripts/pi/init/ssh-tunnels/ag-ssh-tunnels.upstart.conf /etc/init/ag-ssh-tunnels.conf || bailout "$LINENO: Failed to copy init script(s)!"

		echo "[$STAGE] You must now edit the SSH-Tunnels init script to correctly set the port number(s), etc., to use (or you can just quit vim and do it later)."
		echo "[$STAGE] Press [ENTER] to proceed..."
		read x
		sudo vim /etc/init/ag-ssh-tunnels.conf

		echo "[$STAGE] Reconfiguring syslog to filter out the spammy autossh messages..."
		{
			SYSLOG_FILTER_CONTENTS=<<<-EOFILE
			# AutoSSH is super-spammy! :(
			:syslogtag, contains, "autossh" /var/log/autossh.log
			& stop
			EOFILE
			echo "$SYSLOG_FILTER_CONTENTS" | sudo tee /etc/rsyslog.d/30-filter.conf
		} || bailout "$LINENO: Failed to write to /etc/rsyslog.d/30-filter.conf!"
		sudo /etc/init.d/rsyslog force-reload || bailout "$LINENO: Failed to reload rsyslog!"
         */
    }
}
