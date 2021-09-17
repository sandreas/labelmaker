<?php

namespace LabelMaker\Commands;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;
use Psr\Log\LogLevel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
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
        // $this->addArgument(static::ARGUMENT_INPUT, InputArgument::REQUIRED, 'Input file or folder');
//        $this->addOption(static::OPTION_LOG_FILE, null, InputOption::VALUE_OPTIONAL, "file to log all output", "");
//        $this->addOption(static::OPTION_DEBUG, null, InputOption::VALUE_NONE, "enable debug mode - sets verbosity to debug, logfile to m4b-tool.log and temporary encoded files are not deleted");
//        $this->addOption(static::OPTION_FORCE, "f", InputOption::VALUE_NONE, "force overwrite of existing files");
//        $this->addOption(static::OPTION_TMP_DIR, null, InputOption::VALUE_OPTIONAL, "use this directory for creating temporary files");
//
//        $this->addOption(static::OPTION_NO_CLEANUP, null, InputOption::VALUE_NONE, "do not cleanup generated metadata files (e.g. <filename>.chapters.txt)");
//        $this->addOption(static::OPTION_NO_CACHE, null, InputOption::VALUE_NONE, "clear cache completely before doing anything");
//        $this->addOption(static::OPTION_FFMPEG_THREADS, null, InputOption::VALUE_OPTIONAL, "specify -threads parameter for ffmpeg - you should also consider --jobs when merge is used", "");
//        $this->addOption(static::OPTION_PLATFORM_CHARSET, null, InputOption::VALUE_OPTIONAL, "Convert from this filesystem charset to utf-8, when tagging files (e.g. Windows-1252, mainly used on Windows Systems)", "");
//        $this->addOption(static::OPTION_FFMPEG_PARAM, null, InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, "Add argument to every ffmpeg call, append after all other ffmpeg parameters (e.g. --" . static::OPTION_FFMPEG_PARAM . '="-max_muxing_queue_size" ' . '--' . static::OPTION_FFMPEG_PARAM . '="1000" for ffmpeg [...] -max_muxing_queue_size 1000)', []);
//        $this->addOption(static::OPTION_SILENCE_MIN_LENGTH, "a", InputOption::VALUE_OPTIONAL, "silence minimum length in milliseconds", static::SILENCE_DEFAULT_LENGTH);
//        $this->addOption(static::OPTION_SILENCE_MAX_LENGTH, "b", InputOption::VALUE_OPTIONAL, "silence maximum length in milliseconds", 0);
//        $this->addOption(static::OPTION_MAX_CHAPTER_LENGTH, null, InputOption::VALUE_OPTIONAL, "maximum chapter length in seconds - its also possible to provide a desired chapter length in form of 300,900 where 300 is desired and 900 is max - if the max chapter length is exceeded, the chapter is placed on the first silence between desired and max chapter length", "0");

    }


    function dasherize($string)
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
