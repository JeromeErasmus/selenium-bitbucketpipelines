<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

ini_set('display_errors',1);
error_reporting(E_ALL);

define('ADMINSTRATOR', 3);
define('MY_JOBS_IN_PROGRESS', 5);
define('ALL_JOBS_IN_PROGRESS', 6);
define('LAST_PREBUILT_FILTER', 6);

require_once __DIR__.'/../../../vendor/autoload.php';

/**
 * Description of FilterTest
 *
 * @author michael
 */
use App\Config\Config;
use Elf\Application\Application;
use Elf\Exception\UnauthorizedException;
use App\Controllers\Filter;


class FilterTest extends \PHPUnit_Framework_TestCase {

    public function testGetFilterById()
    {
        $filterComparison =  json_decode(file_get_contents(__DIR__.'/data/Filter/singleFilter.json'), true);

        try
        {
            $config = new Config();

            $config->test['request']['server'] = array(
                'REQUEST_URI' => '/filter/clientId/1/id/9',
                'ENVIRONMENT' => 'test',
            );

            $config->test['request']['headers'] = array(
                //'Authorization' => 'Complicated YWRhbS5zeW5ub3R0OkNyZWF0aXZlQ29kaW5nMTQ=',
                'User-Agent' => 'Chrome/43.0.2357.124',
            );


            $config->init(); // init the config again with new configs

            $app = new Application($config);

            $filter = new Filter($app);
            $retrievedFilter = $filter->handleGet($app->request());
//            var_dump($retrievedFilter);exit;
            /* check if all the keys exist */
            foreach(array_keys($filterComparison) as $key) 
            {
                if (array_key_exists($key, $retrievedFilter)) 
                {
                    $this->assertTrue(true);
                } else {
                    $this->assertTrue(false);
                }
            }

            foreach(array_keys($filterComparison['filterDetails']) as $key)
            {
                if (array_key_exists($key, $retrievedFilter['filterDetails'])) 
                {
                    $this->assertTrue(true);
                } else {
                    $this->assertTrue(false);
                }
            }
        }
        catch(UnauthorizedException $exception)
        {
            $this->fail($exception->getMessage());
        }
        catch(\Exception $exception)
        {
            $this->fail($exception->getMessage());
        }
    }

    public function testCollection()
    {
        $filterComparison =  json_decode(file_get_contents(__DIR__.'/data/Filter/singleFilter.json'), true);

        try
        {
            $config = new Config();

            $config->test['request']['server'] = array(
                'REQUEST_URI' => '/filter/clientId/1/type/all',
                'ENVIRONMENT' => 'test',
            );

            $config->test['request']['headers'] = array(
                //'Authorization' => 'Complicated YWRhbS5zeW5ub3R0OkNyZWF0aXZlQ29kaW5nMTQ=',
                'User-Agent' => 'Chrome/43.0.2357.124',
            );


            $config->init(); // init the config again with new configs

            $app = new Application($config);

            $filter = new Filter($app);
            $retrievedFilters = $filter->handleGet($app->request());

            $randIndex = rand(0, count($retrievedFilters)-1);
            $actualFilter = $retrievedFilters[$randIndex];
            $id = $actualFilter["filterId"];

            if ($actualFilter === NULL) {
                var_dump($retrievedFilters);
                $this->fail("Actual Filter $randIndex is NULL");
            }
            /* check if all the keys exist */
            foreach(array_keys($filterComparison) as $key) {
                if (array_key_exists($key, $actualFilter)) {
                    $this->assertTrue(true);
                } else {
                    $this->fail("Array key $key doesn't exist");
                }
            }

            if ($actualFilter['filterDetails'] == null ) 
            {
                $this->fail("Filter Details are NULL when they need all field names (\$id = $id)");
            }

            foreach(array_keys($filterComparison['filterDetails']) as $key) 
            {
                if (array_key_exists($key, $actualFilter['filterDetails'])) 
                {
                    $this->assertTrue(true);
                } else {
                    $this->assertTrue(false);
                }
            }
        }
        catch(UnauthorizedException $exception)
        {
            $this->fail($exception->getMessage());
        }
        catch(\Exception $exception)
        {
            $this->fail($exception->getMessage());
        }
    }

    public function testCreateFilter()
    {
        $filterComparison =  json_decode(file_get_contents(__DIR__.'/data/Filter/postFilter.raw'), true);

        try
        {
            $config = new Config();

            $config->test['request']['server'] = array(
                'REQUEST_URI' => '/filter/clientId/1/',
                'REQUEST_METHOD' => 'POST',
                'rawPostInput' => file_get_contents(__DIR__. DIRECTORY_SEPARATOR .  'data' . DIRECTORY_SEPARATOR . 'Filter' .DIRECTORY_SEPARATOR . 'postFilter.raw'),
                'ENVIRONMENT' => 'test',
            );
            $config->test['request']['headers'] = array(
                //'Authorization' => 'Complicated YWRhbS5zeW5ub3R0OkNyZWF0aXZlQ29kaW5nMTQ=',
                'User-Agent' => 'Chrome/43.0.2357.125',
                'Content-Type' => 'application/json',
            );

            $config->init(); // init the config again with new configs

            $app = new Application($config);

            $filter = new Filter($app);
            $filter->default_event($app->request());

            $data = $filter->getAll();

            $this->assertTrue($data['status_code'] === 201);
            $location = explode('/', $data['locationUrl']);
            $id = end($location);
            $filter = new \App\Models\Filter($app);
            $filterResult = $filter->getFilterById($id);

            if (isset($filterResult['createdAt'])) {
                unset($filterResult['createdAt']);
            }
            if (isset($filterComparison['customFilter'])) {
                unset($filterComparison['customFilter']);
            }

            // now check if they filterName
            foreach(array_keys($filterComparison) as $key) 
            {
                if (array_key_exists($key, $filterResult)) 
                {
                    $this->assertTrue(true);
                } else {
                    var_dump("$key doesnt match");
                    $this->assertTrue(false);
                }
            }

            // check if the filter details match
            $this->assertEquals($filterComparison['filterDetails'], $filterResult['filterDetails']);

            return $id;


        }
        catch(UnauthorizedException $exception)
        {
            $this->fail($exception->getMessage());
        }
        catch(\Exception $exception)
        {
            print "Failed test. Insert ID (if set) is $id";
            $this->fail($exception->getMessage());
        }

    }


    public function testModifyFilter()
    {
        $id = 9;        //make sure this exists in the database!

        /* add a timestamp to make sure each test is different */
        $rawInput = json_decode(file_get_contents(__DIR__. DIRECTORY_SEPARATOR .  'data' . DIRECTORY_SEPARATOR . 'Filter' . DIRECTORY_SEPARATOR . 'patchFilter.raw'), true);
        $description = "automatedTest".time();
        $rawInput['filterDetails']['description'] = $description;
        $rawInput['filterName'] = $description;


        try
        {
            $config = new Config();

            $config->test['request']['server'] = array(
                'REQUEST_URI' => "/filter/clientId/1/id/$id",
                'REQUEST_METHOD' => 'PATCH',
                'rawPostInput' => json_encode($rawInput),
                'ENVIRONMENT' => 'test',
            );

            $config->test['request']['headers'] = array(
//                'Authorization' => 'Complicated YWRhbS5zeW5ub3R0OkNyZWF0aXZlQ29kaW5nMTQ=',
                'Content-Type' => 'application/json',
            );
            $config->init(); // init the config again with new configs

            $app = new Application($config);

            $filterMdl = new \App\Models\Filter($app);

            $previousData = $filterMdl->getFilterById($id);        //store the previous data so we can restore

            if ( $previousData === false ) {
                $this->fail("ID doesn't exist.");
                return;
            }

            $patchFilter = new Filter($app);
            $patchFilter->default_event($app->request());        //now patch the existing filter

            $data = $patchFilter->getAll();

            if ( $data['status_code'] != 204 ){
                $this->fail("PATCH didn't modify filter. HTTP Code {$data['status_code']}. Expected 204.");
                return;
            }

            /* now verify patch worked */
            $modifiedData = $filterMdl->getFilterById($id);

            if (isset($rawInput['filterName'])) 
            {
                if( $rawInput['filterName']  == $modifiedData['filterName'] ) 
                {        //the name was changed - good
                    $this->assertTrue(true);
                } 
                else 
                {
                    $this->assertTrue(false);
                }
            }
//            var_dump($rawInput['filterDetails']);
//            var_dump($modifiedData);
//            exit;
            foreach($rawInput['filterDetails'] as $name => $val)
            {
                if (array_key_exists($name, $modifiedData['filterDetails'])) 
                {
                    if ($val == $modifiedData['filterDetails'][$name]) 
                    {
                        $this->assertTrue(true);
                    } 
                    else 
                    {
                        $this->fail("PATCHED data retrieved does not match input.");
                    }
                } 
                else 
                {
                    $this->fail("PATCHED data retrieved does not match input.");
                }
            }

            //test passed revert back to old data
            $filterMdl->modifyFilter($id, $previousData);

            $revertedData = $filterMdl->getFilterById($id);

            $this->assertNotEmpty($revertedData);
            $this->assertEquals($previousData, $revertedData); //make everything is back to the way it was

        }
        catch(UnauthorizedException $exception)
        {
            $this->fail($exception->getMessage());
        }
        catch(\Exception $exception)
        {
            print "Failed PATCH test. Insert ID (if set) is $id";
            $this->fail($exception->getMessage());
        }
    }

    /**
     * @depends testCreateFilter
     *
     */
    public function testDeleteFilter($id)
    {
        $config = new Config();

        $config->test['request']['server'] = array(
            'REQUEST_URI' => '/job/clientId/1/id/'.$id,
            'REQUEST_METHOD' => 'DELETE',
            'ENVIRONMENT' => 'test',
        );
        $config->test['request']['headers'] = array(
            //'Authorization' => 'Complicated YWRhbS5zeW5ub3R0OkNyZWF0aXZlQ29kaW5nMTQ=',
            'User-Agent' => 'Chrome/43.0.2357.125',
        );

        $config->init();

        $app = new Application($config);

        $filter = new Filter($app);
        $filter->default_event($app->request());

        $data = $filter->getAll();

        if ($data['status_code'] != 204) 
        {      // something went wrong
            $this->fail("Deleting id $id failed. Error: {$data['data']} ");
        }

        /* now try to select the filter again - should return false */
        $config = new Config();
       $config->test['request']['server'] = array(
            'REQUEST_URI' => '/job/clientId/1/id/'.$id,
            'ENVIRONMENT' => 'test',
        );
        $config->test['request']['headers'] = array(
            'Content-Type' => 'application/json',
        );
        
        $config->init();
        $app = new Application($config);

        $filter = new App\Models\Filter($app);

        if ($filter->getFilterById($id) === false) 
        {
            $this->assertTrue(true);
            return true;
        } 
        else 
        {
            $this->fail("Filter failed to delete ID $id.");
            return false;
        }
    }
}