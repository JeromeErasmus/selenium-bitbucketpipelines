<?php
// instantiate console application  example: php console.php TvcExtract --ENVIRONMENT="lucaconfadev"
// get the arguments from command line
// get the command
// run
require_once(__DIR__.'/vendor/autoload.php');

use App\Config\Config;
use Elf\Application\ConsoleApplication;

$environment = $argv[2];

$environment = preg_replace("/[-\"]/", "", $environment);

putenv($environment);

try {
    
    $config = new Config(); // get application specific configuration

    $app = new ConsoleApplication($config);

    $app->getArguments();
    $app->getCommand()->run();

}
catch(\Exception $exception)
{
    $notifyTo = array(
            "freetv@4mation.com.au"
        );
    foreach($notifyTo as $contact){
        mail($contact, 'TVC scheduled task error', $exception);
    }
}
exit;


