<?php

namespace Aziraphale\RaspberryPiSetup\Util;

use Aziraphale\RaspberryPiSetup\Exception\NoSuchStageException;
use Aziraphale\RaspberryPiSetup\Exception\SkipThisStageException;
use Aziraphale\RaspberryPiSetup\Exception\StageManagerException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

class StageManager
{
    /**
     * @var string
     */
    private $stageFile;

    /**
     * If true, we've requested to only LIST the stages that exist, not
     *  actually run through any
     *
     * @var bool
     */
    private $listOnly = false;

    /**
     * @var int
     */
    private $startFrom;

    /**
     * @var int
     */
    private $onlyOneStage;

    /**
     * @var string
     */
    private $stagesDir;

    /**
     * @var string
     */
    private $stagesListFile;

    /**
     * @var InputInterface
     */
    private $input;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var Bailout
     */
    private $bailout;

    /**
     * @var \stdClass
     */
    private $config;

    /**
     * @var StageInterface[]
     */
    private $stages;

    /**
     * @var int
     */
    private $currentStageNumber;

    /**
     * Array of stage numbers which have thrown a SkipThisStageException
     *
     * @var int[]
     */
    private $stagesToSkip = [];

    /**
     * @var bool
     */
    private $optionsPassed = false;

    /**
     * StageManager constructor.
     *
     * @param string          $stageFile Pass FALSE if only wanting to LIST stages
     * @param string          $stagesDir
     * @param string          $stagesListFile
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @param Bailout         $bailout
     * @param \stdClass       $config
     */
    public function __construct($stageFile, $stagesDir, $stagesListFile, InputInterface $input, OutputInterface $output, Bailout $bailout, $config)
    {
        $this->stageFile = $stageFile;
        $this->stagesDir = $stagesDir;
        $this->stagesListFile = $stagesListFile;
        $this->input = $input;
        $this->output = $output;
        $this->bailout = $bailout;
        $this->config = $config;

        $this->itemiseStages();
    }

    public function setOptions($listOnly, $startFrom, $onlyStage, $config)
    {
        $this->listOnly = $listOnly;
        $this->startFrom = $startFrom;
        $this->onlyOneStage = $onlyStage;
        $this->config = $config;
        $this->optionsPassed = true;
    }

    /**
     * Finds all stage classes in the $this->stagesDir and keeps track of them,
     *  stored with the stage number as an array key and the stage class name
     *  as an array value, inside $this->stages
     *
     * return string[]
     */
    private function itemiseStages()
    {
        $stages = [];
        $stageFiles = [];

        if ($this->stagesListFile && file_exists($this->stagesListFile)) {
            $stageFiles = explode("\n", file_get_contents($this->stagesListFile));
        }

        if (!$stageFiles) {
            $finder = new Finder();
            $finder->files()->in($this->stagesDir)->ignoreDotFiles(true)->name('/^\d+-|\.php$/i');
            foreach ($finder as $file) {
                $stageFiles[] = $file->getFilename();
            }
        }

        if (count($stageFiles) < 1) {
            throw new StageManagerException("Unable to find any stages!");
        }

        foreach ($stageFiles as $stageFile) {
            $className = preg_replace('/^\d+-|\.php$/i', '', $stageFile);
            $className = 'Aziraphale\\RaspberryPiSetup\\Stage\\' . $className;

            require_once $this->stagesDir . "/" . $stageFile;
            /** @var StageInterface $stage */
            $stage                = new $className($this->input, $this->output, $this->bailout, $this->config);
            $stageNumber          = (int) $stage->getNumber();
            $stages[$stageNumber] = $stage;
        }

        ksort($stages);

        $this->stages = $stages;
        return $this->stages;
    }

    private function determineInitialStageNumber()
    {
        try {
            if (!$this->optionsPassed) {
                throw new StageManagerException("Calling private method StageManager->determineInitialStageNumber() ".
                                                "before the public StageManager->setOptions() method has been called ".
                                                "will result in incorrect behaviour!");
            }

            if ($this->startFrom !== null) {
                if (array_key_exists($this->startFrom, $this->stages)) {
                    $this->currentStageNumber = (int) $this->startFrom;
                    return $this->currentStageNumber;
                } else {
                    throw new StageManagerException("Requested to start from a stage number that doesn't exist!");
                }
            }
            if ($this->onlyOneStage !== null) {
                if (array_key_exists($this->onlyOneStage, $this->stages)) {
                    $this->currentStageNumber = (int) $this->onlyOneStage;
                    $this->stagesToSkip = array_diff(array_keys($this->stages), [$this->onlyOneStage]);
                    return $this->currentStageNumber;
                } else {
                    throw new StageManagerException("Requested to only execute a stage number that doesn't exist!");
                }
            }

            if (!file_exists($this->stageFile)) {
                $this->currentStageNumber = 0;

                if (!$this->storeCurrentStageNumber()) {
                    throw new StageManagerException("Unable to initialise new stage-tracking file!");
                }

                return $this->currentStageNumber;
            } elseif (!is_readable($this->stageFile)) {
                throw new StageManagerException("Unable to read existing stage-tracking file!");
            }

            $contents = trim(file_get_contents($this->stageFile));
            if ($contents != (int) $contents) {
                throw new StageManagerException("Stage-tracking file has invalid contents!");
            }

            // File contains just an integer, so seems good!
            $stageNum = (int) $contents;
            if (!array_key_exists($stageNum, $this->stages)) {
                throw new StageManagerException("Stage-tracking file has invalid contents!");
            }

            $this->currentStageNumber = $stageNum;
            return $this->currentStageNumber;
        } catch (StageManagerException $ex) {
            $this->bailout->writeln($ex->getMessage())->bail();
            die();
        }
    }

    public function getAllStages()
    {
        return $this->stages;
    }

    public function storeCurrentStageNumber()
    {
        if (array_key_exists($this->currentStageNumber, $this->stages)) {
            return file_put_contents($this->stageFile, $this->currentStageNumber);
        } else {
            $this->cleanUp();
            return true;
        }
    }

    public function hasNextStage()
    {
        return array_key_exists($this->currentStageNumber, $this->stages);
    }

    public function getNextStageNumber()
    {
        if (!isset($this->currentStageNumber)) {
            $this->determineInitialStageNumber();
        }
        return $this->currentStageNumber;
    }

    /**
     * @return StageInterface
     */
    public function getNextStage()
    {
        if (!$this->hasNextStage()) {
            throw new NoSuchStageException("There is no stage number {$this->currentStageNumber}!");
        }

        return $this->stages[$this->currentStageNumber];
    }

    public function getNextStageName()
    {
        return $this->getNextStage()->getName();
    }

    public function getNextStageDescription()
    {
        return $this->getNextStage()->getDescription();
    }

    public function letAllStagesAskPreRunQuestions()
    {
        foreach ($this->stages as $stageNum => $stage) {
            if ($stageNum < $this->currentStageNumber) {
                // Only ask for stages at and beyond our current stage number
                continue;
            }

            try {
                $stage->askPreRunQuestions();
            } catch (SkipThisStageException $ex) {
                // Stage has requested to be skipped, so we just move to the
                //  next while making sure that the main body of the stage
                //  isn't run either
                $this->stagesToSkip[] = $stageNum;
            }
        }

        // Just in case we've now been asked to skip the first stage or five
        $this->progressPastSkippedStages();
    }

    private function progressPastSkippedStages()
    {
        while (in_array($this->currentStageNumber, $this->stagesToSkip, true)) {
            $this->currentStageNumber++;
        }
    }

    public function executeNextStage()
    {
        $this->getNextStage()->run();
        $this->currentStageNumber++;

        $this->progressPastSkippedStages();
    }

    public function cleanUp()
    {
        @unlink($this->stageFile);
    }
}
