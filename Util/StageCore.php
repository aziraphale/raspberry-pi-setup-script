<?php

namespace Aziraphale\RaspberryPiSetup\Util;

use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Process\Process;

abstract class StageCore
{
    const ASKING_SRC_INIT = 'init';
    const ASKING_SRC_PROCESS = 'process';

    /**
     * @var QuestionHelper
     */
    protected static $questionHelper;

    /**
     * @var InputInterface
     */
    protected $input;

    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @var Bailout
     */
    protected $bailout;

    /**
     * @var int
     */
    protected $_stageNum;

    /**
     * @var string
     */
    protected $_stageName;

    /**
     * @var string
     */
    protected $_stageDesc;

    /**
     * StageCore constructor.
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @param Bailout         $bailout
     * @param bool            $listOnly
     * @param int             $stageNumber
     * @param string          $stageName
     * @param string          $stageDescription
     */
    public function __construct(
        InputInterface $input, OutputInterface $output, Bailout $bailout,
        $stageNumber, $stageName, $stageDescription
    )
    {
        $this->input      = $input;
        $this->output     = $output;
        $this->bailout    = $bailout;
        $this->_stageNum  = $stageNumber;
        $this->_stageName = $stageName;
        $this->_stageDesc = $stageDescription;

        if (!isset(static::$questionHelper)) {
            static::$questionHelper = new QuestionHelper();
        }
    }

    /**
     * @return int
     */
    public function getNumber()
    {
        return $this->_stageNum;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->_stageName;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->_stageDesc;
    }

    public function askPreRunQuestions()
    {
        // Do nothing; stub method so that stages don't have to implement this
        //  if they don't need to ask pre-run questions, but the main code can
        //  call this method anyway
    }

    private function getClassNameForConfigId()
    {
        $className = get_class($this);
        $className = preg_replace('/[^A-Za-z0-9]/', '', $className);
        return $className;
    }

    private function getConfigValueIfPresent($questionId)
    {
        $classId = $this->getClassNameForConfigId();

        // @todo load the config file once (somewhere in index.php), but only if passed as an option. then make available to stages
        // @todo return value from this method; have question methods call this method, then have each validate the answer depending on the type of question they ask
        return '';
    }

    protected function pressEnterToContinue($prompt = "Press ENTER to continue...")
    {
        $conf = new ConfirmationQuestion($prompt, true, '/./');
        static::$questionHelper->ask($this->input, $this->output, $conf);
    }

    protected function askForConfirmation($questionIdString = null, $prompt = "Do you wish to continue? [Y/N]", $default = true, $yesRegexp = '/^y/i', callback $validator = null)
    {
        if ($yesRegexp === null) {
            $yesRegexp = '/^y/i';
        }

        if ($questionIdString !== null) {
            $configValue = $this->getConfigValueIfPresent($questionIdString);
            if ($configValue !== null) {
                // @todo validate answer using $yesRegexp, and $validator if set

                // @todo If valid config value, return $configValue;, else continue asking...
            }
        }

        $question = new ConfirmationQuestion($prompt, $default, $yesRegexp);
        if ($validator !== null) {
            $question->setValidator($validator);
        }
        return static::$questionHelper->ask($this->input, $this->output, $question);
    }

    protected function askForString($questionIdString = null, $prompt = "Please enter:", $default = null, callback $validator = null)
    {
        if ($questionIdString !== null) {
            $configValue = $this->getConfigValueIfPresent($questionIdString);
            if ($configValue !== null) {
                // @todo validate answer using $validator if set

                // @todo If valid config value, return $configValue;, else continue asking...
            }
        }

        $question = new Question($prompt, $default);
        if ($validator !== null) {
            $question->setValidator($validator);
        }
        return static::$questionHelper->ask($this->input, $this->output, $question);
    }

    protected function askForChoice($questionIdString = null, $prompt = "Choose one:", array $options, $defaultIndex = null, $errorMessage = null, callback $validator = null)
    {
        if ($questionIdString !== null) {
            $configValue = $this->getConfigValueIfPresent($questionIdString);
            if ($configValue !== null) {
                // @todo validate answer using $options, and $validator if set

                // @todo If valid config value, return $configValue;, else continue asking...
            }
        }

        $question = new ChoiceQuestion($prompt, $options, $defaultIndex);
        if ($errorMessage !== null) {
            $question->setErrorMessage($errorMessage);
        }
        if ($validator !== null) {
            $question->setValidator($validator);
        }
        return static::$questionHelper->ask($this->input, $this->output, $question);
    }

    protected function newProcess($command, $cwd = null, $env = null, $input = null, $timeout = null, $options = [])
    {
        return new Process($command, $cwd, $env, $input, $timeout, $options);
    }

    protected function newProcessTty($command, $cwd = null, $env = null, $input = null, $timeout = null, $options = [])
    {
        $process = new Process($command, $cwd, $env, $input, $timeout, $options);
        $process->setTty(true);
        return $process;
    }
}
