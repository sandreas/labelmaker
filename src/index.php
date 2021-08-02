<?php
require __DIR__.'/../vendor/autoload.php';

use Symfony\Component\Console\Application;
use LabelMaker\Commands\CreateCommand;




register_shutdown_function(function () {
    if (!is_null($e = error_get_last())) {
        echo "an error occured, that has not been caught:\n";
        print_r($e);
    }
});
if (!ini_get('date.timezone')) {
    $timezone = date_default_timezone_get();
    if (!$timezone) {
        $timezone = "UTC";
    }
    date_default_timezone_set($timezone);
}


$application = new Application("labelmaker");

try {
    $application = new Application('labelmaker', '@package_version@');
    $commands = [
        new CreateCommand(),
    ];
    $application->addCommands($commands);
    $application->run();
} catch (Exception $e) {
    echo "uncaught exception: " . $e->getMessage();
}
