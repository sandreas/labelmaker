<?php
require __DIR__.'/../vendor/autoload.php';

use Symfony\Component\Console\Application;
use LabelMaker\Commands\CreateCommand;

$application = new Application("labelmaker");


$application->add(new CreateCommand());

$application->run();
