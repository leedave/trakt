<?php
//This nifty code converts PHP Errors from old functions into Exceptions for 
//better Handling
set_error_handler(function($errno, $errstr, $errfile, $errline){ 
    if (!(error_reporting() & $errno)) {
        return;
    }
    
    throw new \ErrorException($errstr, $errno, 0, $errfile, $errline);

});

require_once __DIR__.'/../vendor/autoload.php';
require_once __DIR__.'/../configs/constants.php';
require_once __DIR__.'/../src/autoload.php';

$time = microtime(true);

echo "Refreshing Collections\n";
$collection = new \Leedch\Trakt\Trakt\Users\Collection();
$collection->refreshCollection("movies");
$collection->refreshCollection("shows");

echo "Refreshing Ratings\n";
$rating = new \Leedch\Trakt\Trakt\Users\Rating();
$rating->refreshData();

echo "Refreshing Watched\n";
$watched = new \Leedch\Trakt\Trakt\Users\History();
$watched->refreshData();

$endTime = round(microtime(true)-$time, 2);
echo "Finished import new Data in ".$endTime."s\n";

//It is best practice to reset error handling when finished
restore_error_handler();