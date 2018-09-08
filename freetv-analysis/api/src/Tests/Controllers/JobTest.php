<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
ini_set('display_errors',1);
error_reporting(E_ALL);

require_once __DIR__.'/../../../vendor/autoload.php';

/**
 * Description of JobTest
 *
 * @author michael
 */
use App\Config\Config;
use Elf\Application\Application;
use Elf\Exception\UnauthorizedException;
use App\Controllers\Job;


class JobTest extends \PHPUnit_Framework_TestCase {
      
  /*
   * Tests retrieving a single job 
   */
    public function testGet()
    {   
        /* first test retrieving a single job */
        $jobComparison =  json_decode(file_get_contents(__DIR__.'/data/Job/singleJob.json'), true);
        
        try 
        {
            $config = new Config();

            $config->test['request']['server'] = array(
                'REQUEST_URI' => '/job/clientId/1/id/1040658',
                'ENVIRONMENT' => 'test',
            );

            $config->test['request']['headers'] = array(
                //'Authorization' => 'Complicated YWRhbS5zeW5ub3R0OkNyZWF0aXZlQ29kaW5nMTQ=',
                'User-Agent' => 'Chrome/43.0.2357.124',
            );

            $config->init(); // init the config again with new configs
            
            $app = new Application($config);
 
            $job = new Job($app);
            $actualJob = $job->handleGet($app->request());

            /* check if all the keys exist */
            foreach(array_keys($jobComparison) as $key) {
                if (array_key_exists($key, $actualJob)) {
                    $this->assertTrue(true);
                } else {
                    $this->fail("Key $key doesn't exist.");
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
    
    /**
     * Test retrieving the entire (top 999) job collection summary
     */
    public function testCollection()
    {
        /* now test job collection (testing keys) */
        $jobComparison =  json_decode(file_get_contents(__DIR__.'/data/singleJobSummary.json'), true);
        
        try 
        {
            $config = new Config();
            $config->test['request']['server'] = array(
                'REQUEST_URI' => '/job/clientId/1/',
                'ENVIRONMENT' => 'test',
            );

            $config->test['request']['headers'] = array(
                //'Authorization' => 'Complicated YWRhbS5zeW5ub3R0OkNyZWF0aXZlQ29kaW5nMTQ=',
                'User-Agent' => 'Chrome/43.0.2357.124',
            );

            $config->init(); // init the config again with new configs
            $app = new Application($config);
            $job = new Job($app);
            $jobcollection = $job->handleGet($app->request());
            
            $randomJob = $jobcollection[rand(0,count($jobcollection))];      //select a random job
            // check if all the keys exist
            foreach(array_keys($jobComparison) as $key) {
                if (array_key_exists($key, $randomJob)) {
                    $this->assertTrue(true);
                } else {
                    $this->fail("Error: $key doesn't exist.");
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
    
    

    public function testPost()
    {
       $jobComparison =  json_decode(file_get_contents(__DIR__.'/data/Job/singleJob.json'), true);
       try 
        {
            $config = new Config();

            $config->test['request']['server'] = array(
                'REQUEST_URI' => '/job/clientId/1/',
                'REQUEST_METHOD' => 'POST',
                'rawPostInput' => file_get_contents(__DIR__. DIRECTORY_SEPARATOR .  'data' . DIRECTORY_SEPARATOR . 'Job'.DIRECTORY_SEPARATOR. 'postJob.raw'),
                'ENVIRONMENT' => 'test',
            );

            $config->test['request']['headers'] = array(
                //'Authorization' => 'Complicated YWRhbS5zeW5ub3R0OkNyZWF0aXZlQ29kaW5nMTQ=',
                'User-Agent' => 'Chrome/43.0.2357.124',
                'Content-Type' => 'application/json',
            );
            $config->init(); // init the config again with new configs
           
            $app = new Application($config);
            $job = new Job($app);
            $job->default_event($app->request());
            $data = $job->getAll();
            
            $this->assertTrue($data['status_code'] === 201);
            $location = explode('/', $data['locationUrl']);
            $id = end($location);
            
            $job = new \App\Models\Job($app);
            $job->setJobId($id);
            $job->load();
            $actualJob = $job->getFullJob();
            foreach(array_keys($jobComparison) as $key) {
                if (array_key_exists($key, $actualJob)) {
                    $this->assertTrue(true);
                } else {
                    $this->fail("Key $key doesn't exist.");
                }
            } 
            
            // now check the values

            //$job->deleteJob$id); // clean up              
          
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
    
 
    /*
     * Picks two random dates which are between a desired date
     * e.g. due after 2014/05/17 and before 2014/05/19. Go through each retrieved
     * job and make sure that the job required by date is 2014/05/18.
     */
    public function testDateFilter()
    {

        $beginningDate = new DateTime('2009-01-01');
        $endDate = new DateTime('2013-12-31');

        $diff = $beginningDate->diff($endDate);

        $randomDate = clone $beginningDate;

        $randomDate->add(new DateInterval('P'.rand(0, $diff->days).'D'));       //get a random date

        /* create two dates between the date to check */
        $dueAfter = clone $randomDate;
        $dueAfter->sub(new DateInterval('P1D'));

        $dueBefore = clone $randomDate;
        $dueBefore->add(new DateInterval('P1D'));

        $url = "/job/clientId/1/dueAfter/{$dueAfter->format('Y-m-d')}/dueBefore/{$dueBefore->format('Y-m-d')}";

        try 
        {
            $jobcollection = $this->retrieveData($url);
            
            while (count($jobcollection) == 0) {        //some dates might be zero

                $randomDate = clone $beginningDate;
                $randomDate->add(new DateInterval('P'.rand(0, $diff->days).'D'));       //get a random date

                /* create two dates between the date to check */
                $dueAfter = clone $randomDate;
                $dueAfter->sub(new DateInterval('P1D'));

                $dueBefore = clone $randomDate;
                $dueBefore->add(new DateInterval('P1D'));

                $url = "/job/clientId/1/dueAfter/{$dueAfter->format('Y-m-d')}/dueBefore/{$dueBefore->format('Y-m-d')}";

                $jobcollection = $this->retrieveData($url);
            }
            
            $randomId = rand(0, count($jobcollection)-1);
            $url2 = "/job/clientId/1/id/{$jobcollection[$randomId]['jobReferenceNo']}";
            $singleJobDate = new DateTime($this->retrieveData($url2)['requiredByDate']);


            /* if the retrieved job is not in the specified date */
            if ( $dueAfter > $singleJobDate || $singleJobDate > $dueBefore ) {
                $this->fail("Retrieved job is outside of filtered date."
                        . "(Job ID $randomId, URL: $url ");
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
    
    public function retrieveData($url)
    {
        $config = new Config();
        $config->test['request']['server'] = array(
            'REQUEST_URI' => "$url",
            'ENVIRONMENT' => 'test',
        );
        //echo $config->test['request']['server']['REQUEST_URI'];

        $config->test['request']['headers'] = array(
            //'Authorization' => 'Complicated YWRhbS5zeW5ub3R0OkNyZWF0aXZlQ29kaW5nMTQ=',
            'User-Agent' => 'Chrome/43.0.2357.124',
        );

        $config->init(); // init the config again with new configs
        $app = new Application($config);
        $job = new Job($app);
        return $job->handleGet($app->request());
    }
}

