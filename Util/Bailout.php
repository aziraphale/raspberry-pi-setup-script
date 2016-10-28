<?php

namespace Aziraphale\RaspberryPiSetup\Util;

use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Bailout
{
    /**
     * @var InputInterface
     */
    private $input;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * Bailout constructor.
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    public function __construct(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;

        $this->output->getFormatter()->setStyle('bailout', new OutputFormatterStyle('red', 'black', ['bold']));
    }

    /**
     * Output a formatted message including a trailing newline. Returns $this for chainability.
     *
     * @param string $msg
     * @return $this
     */
    public function writeln($msg)
    {
        $this->output->writeln("<bailout>$msg</bailout>");
        return $this;
    }

    /**
     * Output a formatted message without a trailing newline. Returns $this for chainability.
     *
     * @param string $msg
     * @return $this
     */
    public function write($msg)
    {
        $this->output->write("<bailout>$msg</bailout>");
        return $this;
    }

    /**
     * Output an unformatted message without a trailing newline. Returns $this for chainability.
     *
     * @param string $msg
     * @return $this
     */
    public function writeRaw($msg)
    {
        $this->output->write($msg);
        return $this;
    }

    /**
     * Exits the script with the specified status code (defaults to 1)
     *
     * @param int $exitStatus
     */
    public function bail($exitStatus = 1)
    {
        die((int) $exitStatus);
    }
}
