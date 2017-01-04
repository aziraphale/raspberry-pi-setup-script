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
     * @var \stdClass
     */
    protected $config;

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
     * @param \stdClass       $config
     * @param int             $stageNumber
     * @param string          $stageName
     * @param string          $stageDescription
     * @internal param bool $listOnly
     */
    public function __construct(
        InputInterface $input, OutputInterface $output, Bailout $bailout, \stdClass $config,
        $stageNumber, $stageName, $stageDescription
    )
    {
        $this->input      = $input;
        $this->output     = $output;
        $this->bailout    = $bailout;
        $this->config     = $config;
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

    private function getClassWithoutNamespace($object = null)
    {
        $className = get_class($object);
        if ($pos = strrpos($className, '\\')) {
            return substr($className, $pos + 1);
        }
        return $pos;
    }

    private function getClassNameForConfigId()
    {
        $className = $this->getClassWithoutNamespace($this);
        $className = preg_replace('/[^A-Za-z0-9]/', '', $className);
        return $className;
    }

    protected function getConfigValueIfPresent($questionId)
    {
        $classId = $this->getClassNameForConfigId();

        if (isset($this->config->{$classId}->{$questionId})) {
            return $this->config->{$classId}->{$questionId};
        }
        return null;
    }

    protected function pressEnterToContinue($prompt = "Press ENTER to continue...")
    {
        $conf = new ConfirmationQuestion($prompt, true, '/./');
        static::$questionHelper->ask($this->input, $this->output, $conf);
    }

    protected function askForConfirmation($questionIdString = null, $prompt = "Do you wish to continue? [Y/N]", $default = true, $yesRegexp = '/^y/i', \Closure $validator = null)
    {
        if ($yesRegexp === null) {
            $yesRegexp = '/^y/i';
        }

        if ($questionIdString !== null) {
            $configValue = $this->getConfigValueIfPresent($questionIdString);
            if ($configValue !== null) {
                if (is_bool($configValue)) {
                    $configValue = $configValue ? 'Y' : 'N';
                }
                $valid = (bool) preg_match('/[yn]/i', $configValue);

                if ($valid && $validator !== null) {
                    try {
                        $configValue = $validator($configValue);
                    } catch (\Exception $ex) {
                        $this
                            ->bailout
                            ->writeln("Validation error with config value ".
                                      $this->getClassNameForConfigId() . "/" . $questionIdString .
                                      ": " . $ex->getMessage());
                        $valid = false;
                    }
                }
                if ($valid) {
                    if (!is_bool($configValue)) {
                        /** @noinspection NotOptimalRegularExpressionsInspection */
                        $configValue = (bool) preg_match($yesRegexp, $configValue);
                    }
                    return $configValue;
                }
            }
        }

        $question = new ConfirmationQuestion($prompt, $default, $yesRegexp);
        if ($validator !== null) {
            $question->setValidator($validator);
        }
        return static::$questionHelper->ask($this->input, $this->output, $question);
    }

    protected function askForString($questionIdString = null, $prompt = "Please enter:", $default = null, \Closure $validator = null)
    {
        if ($questionIdString !== null) {
            $configValue = $this->getConfigValueIfPresent($questionIdString);
            if ($configValue !== null) {
                $valid = true;
                if ($validator !== null) {
                    try {
                        $configValue = $validator($configValue);
                    } catch (\Exception $ex) {
                        $this
                            ->bailout
                            ->writeln("Validation error with config value ".
                                      $this->getClassNameForConfigId() . "/" . $questionIdString .
                                      ": " . $ex->getMessage());
                        $valid = false;
                    }
                }

                if ($valid) {
                    return $configValue;
                }
            }
        }

        $question = new Question($prompt, $default);
        if ($validator !== null) {
            $question->setValidator($validator);
        }
        return static::$questionHelper->ask($this->input, $this->output, $question);
    }

    protected function askForChoice($questionIdString = null, $prompt = "Choose one:", array $options, $defaultIndex = null, $errorMessage = null, \Closure $validator = null)
    {
        if ($questionIdString !== null) {
            $configValue = $this->getConfigValueIfPresent($questionIdString);
            if ($configValue !== null) {
                $inOptions = false;
                foreach ($options as $option) {
                    if (strcasecmp($configValue, $option) === 0) {
                        $configValue = $option;
                        $inOptions = true;
                        break;
                    }
                }
                $valid = $inOptions;

                if ($validator !== null) {
                    try {
                        $configValue = $validator($configValue);
                    } catch (\Exception $ex) {
                        $this
                            ->bailout
                            ->writeln("Validation error with config value ".
                                      $this->getClassNameForConfigId() . "/" . $questionIdString .
                                      ": " . $ex->getMessage());
                        $valid = false;
                    }
                }

                if ($valid) {
                    return $configValue;
                }
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
