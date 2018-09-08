<?php
/**
 * Created by PhpStorm.
 * User: thimira.gunasekera
 * Date: 1/03/2016
 * Time: 2:49 PM
 */
namespace App\Services;

use Elf\Core\Module;
class Automation extends Module
{

    /**
     * gets all the methods in this class (and not parent classes) and executes them
     */
    public function runAll()
    {
        $className = get_class($this);
        $reflect = new \ReflectionClass($className);
        print "Running all scheduled task (".date('d-m-Y H:i:s').")".PHP_EOL;
        foreach ($reflect->getMethods() as $method) {       //we have to use reflection to get the methods only in this class and not parent classes
            if ($method->class == $className && $method->name != __FUNCTION__) {
                $this->{$method->name}();
            }
        }
        print "Finished running all tasks (".date('d-m-Y H:i:s').")".PHP_EOL;

    }


    /**
     * Example usage, which calls model function disableOldAdvertisers()
     *
     * Note best practice usage of echo statements in case logs need to be utilised
     *
     */
//    public function advertiserExpiry()
//    {
//        echo "** Disabling Inactive Advertisers **".PHP_EOL;
//        $advertisers_disabled = $this->app->model('Advertiser')->disableOldAdvertisers();
//        echo "Disabled $advertisers_disabled advertisers".PHP_EOL;
//        echo "** Finished task **".PHP_EOL;
//    }

}