<?php

namespace Aziraphale\RaspberryPiSetup;

require_once __DIR__ . '/vendor/autoload.php';

use Aziraphale\RaspberryPiSetup\Exception\StageException;
use Aziraphale\RaspberryPiSetup\Util\Bailout;
use Aziraphale\RaspberryPiSetup\Util\StageManager;
use Aziraphale\Symfony\SingleCommandApplication;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\Output;
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

    /**
     * @var bool
     */
    private $optList = false;

    /**
     * @var int|null
     */
    private $optStart = null;

    /**
     * @var int|null
     */
    private $optOnly = null;

    /**
     * @var string|null
     */
    private $optConfigFile = null;

    /**
     * @var string|null
     */
    private $optConfigFileFull = null;

    /**
     * @var \stdClass|null
     */
    private $config = null;

    protected function configure()
    {
        $this
            ->setName("Raspberry Pi Setup Command")
            ->setDescription('This will set up a Raspberry Pi system with the common configuration and applications that I like on all my Pis.')

            ->addOption('start', 's', InputOption::VALUE_REQUIRED,
                        "The stage number to start at, instead of starting at stage #0. Note that this script will ".
                        "keep track of where it got to, so it will restart where it left off if interrupted and ".
                        "re-run.")
            ->addOption('only', 'o', InputOption::VALUE_REQUIRED,
                        "Specifies the ONLY stage number to run instead of running them all.")
            ->addOption('list', 'l', InputOption::VALUE_NONE,
                        "If passed, all stages will be listed and none will be run. Overrides 'start' and 'only' ".
                        "options.")
            ->addOption('config', 'c', InputOption::VALUE_REQUIRED,
                        "Specify a configuration .json file to supply answers to prompts so setup can potentially ".
                        "run without interruptions (assuming everything goes to plan).")
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
        // Config defaults to an empty class so that it can be passed around (by-ref) immediately
        $this->config = new \stdClass();

        $this->bailout = new Bailout($input, $output);
        $this->stageManager = new StageManager(
            getcwd() . "/.pi-setup-state",
            __DIR__ . "/Stage/",
            __DIR__ . "/Stage/.stages.txt",
            $input,
            $output,
            $this->bailout,
            $this->config
        );

        $output->getFormatter()->setStyle('info', new OutputFormatterStyle('yellow', null, ['bold']));
        $output->getFormatter()->setStyle('hilight', new OutputFormatterStyle('white', null, ['bold']));
        $output->getFormatter()->setStyle('success', new OutputFormatterStyle('green', null, ['bold']));
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
        $this->saveOptions($input, $output);

        if ($this->optStart !== null && $this->optOnly !== null) {
            $this
                ->bailout
                ->writeln("Passing both 'start' and 'only' options is illogical! We only support one at a time!")
                ->bail(1);
        }
        if ($this->optList === true && ($this->optStart !== null || $this->optOnly !== null)) {
            $this
                ->bailout
                ->writeln("Passing the 'start' or 'only' options along with 'list' is illogical! ".
                          "If 'list' is passed, no stages at all will be run, making specifying which stages ".
                          "are going to run nonsensical!")
                ->bail(1);
        }

        if ($this->optConfigFile !== null && (!is_file($this->optConfigFileFull) || !is_readable($this->optConfigFileFull))) {
            $this
                ->bailout
                ->writeln("Specified config file '{$this->optConfigFile}' [{$this->optConfigFileFull}] either doesn't exist or cannot be read!")
                ->bail(1);
        }

        if ($this->optConfigFile) {
            $configJsonStr = file_get_contents($this->optConfigFileFull);
            if ($configJsonStr === false) {
                $this
                    ->bailout
                    ->writeln("Unable to read the contents of the config file '{$this->optConfigFile}'!")
                    ->bail(1);
            }

            $configJson = @json_decode($configJsonStr);
            if ($configJson === null) {
                $this
                    ->bailout
                    ->writeln("Error decoding JSON of config file: " . json_last_error_msg())
                    ->bail(2);
            }
            // The config object has likely already been passed (by-ref) so we need to set its properties
            //  rather than overwriting it entirely
            foreach ($configJson as $k => $v) {
                $this->config->{$k} = $v;
            }
        }

        //var_dump(['list'=>$this->optList, 'start'=>$this->optStart, 'only'=>$this->optOnly]);
        $this->stageManager->setOptions($this->optList, $this->optStart, $this->optOnly, $this->config);
    }

    protected function saveOptions(InputInterface $input, OutputInterface $output)
    {
        $this->optList       = $input->getOption('list');
        $this->optStart      = $input->getOption('start');
        $this->optOnly       = $input->getOption('only');
        $this->optConfigFile = $input->getOption('config');
        $this->optConfigFileFull = getcwd() . "/" . $this->optConfigFile;
    }

    protected function listStages(InputInterface $input, OutputInterface $output)
    {
        $output->writeln("<info>Available stages:</info>");
        foreach ($this->stageManager->getAllStages() as $stage) {
            $output->writeln("<info>#{$stage->getNumber()} - “{$stage->getName()}”</info>");
            $output->writeln("        " . wordwrap($stage->getDescription(), 90, "\n        "));
        }
        $output->writeln("<info>Call this script with `--start=3` to run all stages from #3 onwards, or call ".
                         "it with `--only=3` to run ONLY stage #3, for example.</info>");
    }

    protected function processStages(InputInterface $input, OutputInterface $output)
    {
        try {
            $stageNo = $this->stageManager->getNextStageNumber();
            $stageName = $this->stageManager->getNextStageName();

            if ($stageNo > 0) {
                if ($this->optStart !== null) {
                    $output->writeln(
                        "<info>Starting from stage #{$stageNo} <hilight>“{$stageName}”</hilight>, " .
                        "as requested.</info>"
                    );
                } elseif ($this->optOnly !== null) {
                    $output->writeln(
                        "<info>Running only stage #{$stageNo} <hilight>“{$stageName}”</hilight>, as requested.</info>"
                    );
                } else {
                    // Been run before; resuming from where we got to...
                    $output->writeln(
                        "<info>This script appears to have been run before; " .
                        "resuming from stage #{$stageNo} <hilight>“{$stageName}”</hilight>...</info>"
                    );
                }
            }

            if (!$this->stageManager->hasNextStage()) {
                // Already at the end?! (StageManager would have thrown an exception earlier if there are no stages)
                $this->bailout->writeln("There are no more setup stages left to be run!")->bail(0);
            }

            if ($this->optOnly === null) {
                $this->stageManager->letAllStagesAskPreRunQuestions();
            }

            do {
                $stageNo = $this->stageManager->getNextStageNumber();
                $stageName = $this->stageManager->getNextStageName();
                $stageDesc = $this->stageManager->getNextStageDescription();

                $output->writeln("<info>Executing stage #{$stageNo} <hilight>“{$stageName}”</hilight>...</info>");
                $output->writeln("<info>“{$stageDesc}”</info>", Output::VERBOSITY_VERBOSE);

                try {
                    $this->stageManager->executeNextStage();

                    if ($this->optOnly === null) {
                        // Obviously don't store our progress if we're only running one stage...
                        $this->stageManager->storeCurrentStageNumber();
                    }
                } catch (StageException $ex) {
                    $this->bailout
                        ->writeln("EXCEPTION THROWN while executing stage #{$stageNo} “{$stageName}”!")
                        ->writeln($ex->getMessage())
                        ->bail(2);
                    break;
                }
            } while ($this->stageManager->hasNextStage());

            $output->writeln("<success>Setup process has completed successfully!</success>");
            $output->writeln("<success>You should now reboot this Pi.</success>");
        } catch (\Exception $ex) {
            $this->bailout
                ->writeln("Unexpected exception `".get_class($ex)."` thrown:")
                ->writeln($ex->getMessage())
                ->writeln($ex->getTraceAsString())
                ->bail(3);
        }
    }

    /**
     * This method is executed after interact() and initialize(). It contains
     *  the logic you want the command to execute.
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($this->optList === true) {
            $this->listStages($input, $output);
        } else {
            $this->processStages($input, $output);
        }
    }
}

(new SingleCommandApplication(new RaspberryPiSetupCommand()))->run();
