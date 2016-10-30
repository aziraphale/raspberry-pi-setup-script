<?php

namespace Aziraphale\RaspberryPiSetup\Util;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class StageCore
{
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
     * @var bool
     */
    protected $listOnly;

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
        InputInterface $input, OutputInterface $output, Bailout $bailout, $listOnly,
        $stageNumber, $stageName, $stageDescription
    )
    {
        $this->input      = $input;
        $this->output     = $output;
        $this->bailout    = $bailout;
        $this->listOnly   = $listOnly;
        $this->_stageNum  = $stageNumber;
        $this->_stageName = $stageName;
        $this->_stageDesc = $stageDescription;
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
}
