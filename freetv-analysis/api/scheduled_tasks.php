<?php
/**
 * Created by PhpStorm.
 * User: thimira.gunasekera
 * Date: 1/03/2016
 * Time: 2:49 PM
 */


require_once('vendor/autoload.php');
use App\Config\Config;
use Elf\Application\ConsoleApplication;

try {

    $config = new Config(); // get application specific configuration
    $app = new ConsoleApplication($config);

    $automation = $app->service('Automation');

    $automation->runAll();

}
catch(\Exception $exception)
{
    echo "ERROR: ".$exception->getMessage();
    exit;
}
exit;