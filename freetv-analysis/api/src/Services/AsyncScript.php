<?php
//error_reporting(E_ALL);
// instantiate console application
// get the arguments from command line
// get the command
// run
require_once(__DIR__.'/../../vendor/autoload.php');
use App\Config\Config;
use Elf\Application\ConsoleApplication;

try {

	$environment = $argv[2];
	
	$environment = preg_replace("/[-\"]/", "", $environment);

	putenv($environment);
	
    $config = new Config(); // get application specific configuration
    $app = new ConsoleApplication($config);
    $app->getArguments();	
	
    $app->getCommand()->run();

}
catch(\Exception $exception)
{
    echo $exception->getMessage();
    exit;
}
exit;