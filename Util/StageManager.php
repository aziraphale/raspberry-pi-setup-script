<?php

namespace Aziraphale\RaspberryPiSetup\Util;

use Aziraphale\RaspberryPiSetup\Exception\NoSuchStageException;
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
     * @var string
     */
    private $stagesDir;

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
     * @var StageInterface[]
     */
    private $stages;

    /**
     * @var int
     */
    private $currentStageNumber;

    /**
     * StageManager constructor.
     *
     * @param string          $stageFile
     * @param string          $stagesDir
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @param Bailout         $bailout
     */
    public function __construct($stageFile, $stagesDir, InputInterface $input, OutputInterface $output, Bailout $bailout)
    {
        $this->stageFile = $stageFile;
        $this->stagesDir = $stagesDir;
        $this->input = $input;
        $this->output = $output;
        $this->bailout = $bailout;

        $this->itemiseStages();
        $this->determineInitialStageNumber();
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
        $finder = new Finder();
        $finder->in($this->stagesDir)->ignoreDotFiles(true)->contains('/.\php$/i');
        foreach ($finder->files() as $file) {
            $className = preg_replace('/\.php$/i', '', $file->getFilename());
            $className = 'Aziraphale\\RaspberryPiSetup\\Stage\\' . $className;

            /** @var StageInterface $stage */
            $stage = new $className($this->input, $this->output, $this->bailout);
            $stageNumber = $stage->getNumber();
            $stages[$stageNumber] = $stage;
        }
        $this->stages = $stages;
        return $this->stages;
    }

    private function determineInitialStageNumber()
    {
        try {
            if (!file_exists($this->stageFile)) {
                if (!file_put_contents($this->stageFile, '0')) {
                    throw new StageManagerException("Unable to initialise new stage-tracking file!");
                }

                return 0;
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

    public function hasNextStage()
    {
        return array_key_exists($this->currentStageNumber, $this->stages);
    }

    public function getNextStageNumber()
    {
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

    public function executeNextStage()
    {
        $this->getNextStage()->run();
        $this->currentStageNumber++;
    }

    public function cleanUp()
    {
        @unlink($this->stageFile);
    }
}
