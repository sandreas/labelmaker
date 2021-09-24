<?php

namespace LabelMaker\Commands;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;
use Psr\Log\LogLevel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractCommand extends Command implements LoggerInterface
{
    use LoggerTrait;

    const ARGUMENT_INPUT = "input";
    const LOG_LEVEL_TO_VERBOSITY = [
        LogLevel::DEBUG => OutputInterface::VERBOSITY_DEBUG,
        LogLevel::NOTICE => OutputInterface::VERBOSITY_VERBOSE,
        LogLevel::INFO => OutputInterface::VERBOSITY_VERY_VERBOSE,
        LogLevel::WARNING => OutputInterface::VERBOSITY_NORMAL,
        LogLevel::ERROR => OutputInterface::VERBOSITY_QUIET,
    ];

    private OutputInterface $output;

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);
        $this->output = $output;
        if(getenv("APP_DEBUG") === "1"){
            $this->output->setVerbosity(OutputInterface::VERBOSITY_DEBUG);
        }
    }


    protected function configure()
    {
        $className = get_class($this);
        $commandName = $this->dasherize(substr($className, strrpos($className, '\\') + 1, -7));
        $this->setName($commandName);
    }


    function dasherize(string $string): string
    {
        return strtolower(preg_replace('/(?<!^)([A-Z])/', '-$1', str_replace('_', '-', $string)));
    }

    public function log($level, $message, array $context = [])
    {
        $verbosity = static::LOG_LEVEL_TO_VERBOSITY[$level] ?? OutputInterface::VERBOSITY_VERBOSE;


        if (!$this->output) {
            echo $message . PHP_EOL;
            return;
        }
        if ($this->output->getVerbosity() < $verbosity) {
            return;
        }

        $formattedLogMessage = $message;
        switch ($level) {
            case LogLevel::WARNING:
                $formattedLogMessage = "<fg=black;bg=yellow>" . $formattedLogMessage . "</>";
                break;
            case LogLevel::ERROR:
            case LogLevel::CRITICAL:
            case LogLevel::EMERGENCY:
                $formattedLogMessage = "<fg=black;bg=red>" . $formattedLogMessage . "</>";
                break;
        }

        $this->output->writeln($formattedLogMessage);
    }
}
