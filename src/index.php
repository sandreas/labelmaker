<?php
declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

use Symfony\Component\Console\Application;
use LabelMaker\Commands\CreateCommand;

// allow mpdf to include inlined svg
$pcreBacktrackLimit = $_ENV["LABELMAKER_PCRE_BACKTRACK_LIMIT"] ?? "10000000";
ini_set("pcre.backtrack_limit", $pcreBacktrackLimit);

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

// fix macintosh line endings
if (!ini_get("auto_detect_line_endings")) {
    ini_set("auto_detect_line_endings", '1');
}

$application = new Application("labelmaker");

try {
    $application = new Application('labelmaker', '@package_version@');
    $commands = [
        new CreateCommand(),
    ];
    $application->addCommands($commands);
    $application->setDefaultCommand($commands[0]->getName());
    $application->run();
} catch (Exception $e) {
    echo "uncaught exception: " . $e->getMessage();
}
