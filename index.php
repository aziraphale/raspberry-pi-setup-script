<?php

namespace Aziraphale\RaspberryPiSetup;

require_once __DIR__ . '/vendor/autoload.php';

use Aziraphale\RaspberryPiSetup\Util\Bailout;
use Aziraphale\RaspberryPiSetup\Util\StageManager;
use Aziraphale\Symfony\SingleCommandApplication;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RaspberryPiSetupCommand extends Command
{
    const VERSION = '1.0.0';

    /**
     * @var Bailout
     */
    private $bailout;

    /**
     * @var StageManager
     */
    private $stageManager;

    protected function configure()
    {
        $this
            ->setName("Raspberry Pi Setup Command")
            ->setDescription('This will set up a Raspberry Pi system with the common configuration and applications that I like on all my Pis.')

            //->addOption('from', 'f', InputOption::VALUE_REQUIRED, "The email's 'From' header",
            //            sprintf('%s@%s', get_current_user(), gethostname()))
            //->addOption('to', 't', InputOption::VALUE_REQUIRED, "The recipient(s) of the email (in the 'To' header)")
            //->addOption('subject', 's', InputOption::VALUE_REQUIRED, "The email's subject")

            //->addArgument('text', InputArgument::REQUIRED, "The text version of the email body. Can also be passed via STDIN by either leaving this argument empty or passing '-' (a single hyphen) as this argument.")
        ;
    }

    /**
     * This method is executed before the interact() and the execute() methods.
     *  Its main purpose is to initialize variables used in the rest of the
     *  command methods.
     *
     * This can be deleted if it is not required.
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->bailout = new Bailout($input, $output);
        $this->stageManager = new StageManager("./.pi-setup-state", "./Stage/", $input, $output, $this->bailout);
    }

    /**
     * This method is executed after initialize() and before execute(). Its
     *  purpose is to check if some of the options/arguments are missing and
     *  interactively ask the user for those values. This is the last place
     *  where you can ask for missing options/arguments. After this command,
     *  missing options/arguments will result in an error.
     *
     * This can be deleted if it is not required.
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {

    }

    private function validation(InputInterface $input, OutputInterface $output)
    {
        //if ($input->getOption('to') === null) {
        //    throw new \InvalidArgumentException("You must include, and supply a value for, the --to/-t option.");
        //}
        //if ($input->getOption('subject') === null) {
        //    throw new \InvalidArgumentException("You must include, and supply a value for, the --subject/-s option.");
        //}
    }

    /**
     * This method is executed after interact() and initialize(). It contains
     *  the logic you want the command to execute.
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            if ($this->stageManager->getNextStageNumber() > 0) {
                // Been run before; resuming from where we got to...
                /** @todo */
            }
            if (!$this->stageManager->hasNextStage()) {
                if ($this->stageManager->getNextStageNumber() == 0) {
                    // No stages found?!

                } else {
                    // Already at the end?!

                }
            }

            while ($this->stageManager->hasNextStage()) {
                // @todo output name/description
                try {
                    $this->stageManager->executeNextStage();
                } catch (\Exception $ex) {
                    // @todo
                    break;
                }
            }

            //$output->writeln("Email accepted for delivery by Mailgun.");
            //$output->writeln("Details (JSON-encoded) returned by Mailgun:", OutputInterface::VERBOSITY_VERBOSE);
        //} catch (GenericHTTPError $ex) {
        //    $output->writeln("HTTP exception `".get_class($ex)."` thrown when attempting to send email via Mailgun:");
        //    $output->writeln($ex->getMessage());
        } catch (\Exception $ex) {
            $output->writeln("Unexpected exception `".get_class($ex)."` thrown:");
            $output->writeln($ex->getMessage());
            $output->writeln($ex->getTraceAsString());
        }
    }
}

(new SingleCommandApplication(new RaspberryPiSetupCommand()))->run();
