<?php

/**
 * Configuration File
 *
 * main entry point - this file will direct the flow of the application.
 *
 * @author     Edward Wong <edward@4mation.com.au>
 */


define('APP_ENV', 'development');
// define('APP_ENV', getenv('ENVIRONMENT'));

// Redirecting from HTTP to HTTPS with PHP (but only on production)
// http://stackoverflow.com/questions/5106313/redirecting-from-http-to-https-with-php
if (APP_ENV == 'production') {
    if(empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] == "off"){
        $redirect = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        header('HTTP/1.1 301 Moved Permanently');
        header('Location: ' . $redirect);
        exit();
    }
}

date_default_timezone_set('Australia/Sydney');


require_once __DIR__.'/../vendor/autoload.php';

use Elf\Application\Application;
use App\Config\Config;
use Elf\Exception\UnauthorizedException;
use App\Services\Permissions\PermissionLoader;


// CONSTANTS TODO: make them dependent on environment
//TODO: remove this one and define them in config.php so when we run from commands from console constants are picked up
define('WESTPAC_TRANSACTION_APPROVED', 0);
define('WESTPAC_TRANSACTION_DECLINED', 1);
define('WESTPAC_TRANSACTION_ERROR', 2);
define('WESTPAC_TRANSACTION_REJECTED', 3);

define('ADMINSTRATOR', 3);
define('MY_JOBS_IN_PROGRESS', 5);
define('ALL_JOBS_IN_PROGRESS', 6);
define('LAST_PREBUILT_FILTER', 6);
define('AGENCY_ACCOUNT_TYPE_ACC', 'ACC');
define('AGENCY_ACCOUNT_TYPE_COD', 'COD');

$allowedIPs = array(
    '192.168.63.1',
    '52.64.51.195',
    '218.185.237.110', //4Mation External IP
    '120.151.150.174', //4Mation External IP
    '127.0.0.1',
    '10.1.102.200',// TODO keep an eye on this one, committed due to staging env change
    '54.206.48.59', // TODO keep an eye on this one, committed due to staging env change
    '220.245.118.120', // TODO keep an eye on this one, committed due to staging env change
	// '220.245.118.113', // TODO keep an eye on this one, committed due to staging env change
	'10.2.80.1',
	'10.3.100.57',
	'10.3.100.65',
    '::1',
    // docker
);

if(!in_array($_SERVER['REMOTE_ADDR'],$allowedIPs) && (strpos($_SERVER['REMOTE_ADDR'],'192.168.') === false) && APP_ENV !== 'development')
{
    print "UNAUTH ACCESS";
    // unauthorised, just show a blank screen
    exit;
}

if (APP_ENV === 'development') {
    $whoops = new \Whoops\Run();
    $whoops->pushHandler(new \Whoops\Handler\JsonResponseHandler());
    $whoops->register();
} else {
    $bugsnag = Bugsnag\Client::make('787d87342f1c9b462a0910139fb6b9bb');
    Bugsnag\Handler::register($bugsnag);
    $bugsnag->setErrorReportingLevel(E_ALL & ~E_NOTICE);
}

try
{
    $config = new Config();
    $app = new Application($config);

    define('ASYNC_SCRIPT', $app->config->defaults['paths']['services'] . DIRECTORY_SEPARATOR . "AsyncScript.php");

    $middleware = $app->service('MiddleWare');
    $middleware->addPermission(PermissionLoader::loadPermission($app->request(), $config, $app));

    $app->handleRequest([$middleware, 'handlePermissions']);

}
catch(UnauthorizedException $exception)
{
    $exceptionDebugMsg = $exception->getDebugMessage();
    $message = !empty($exceptionDebugMsg) ? $exceptionDebugMsg : $exception->getMessage();

    header('HTTP/1.1 403 Forbidden');
    header('Content-Type: application/json');

    echo json_encode(array(
        'code' => 403,
        'message' => $message,
    ));
    exit;
}
catch (\Elf\Exception\ElfException $exception) {
    if (isset($bugsnag)) {
        $bugsnag->notifyException($exception);
    }
    http_response_code($exception->getCode());
    throw $exception;
}catch (Exception $exception)
{
    if (isset($bugsnag)) {
        $bugsnag->notifyException($exception);
    }
    throw $exception;

}
