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
 * Description of TvcCostTest
 *
 * @author Jeremy
 * Based on Michael's work
 */
use App\Config\Config;
use Elf\Application\Application;
use Elf\Exception\UnauthorizedException;
use App\Controllers\TvcCost;


class TvcCostTest extends \PHPUnit_Framework_TestCase {
    
    public function testPost()
        {
           $CostComparison =  json_decode(file_get_contents(__DIR__.'/data/createTvcCost.json'), true);
           
           try 
            {
                $config = new Config();

                $config->test['request']['server'] = array(
                    'REQUEST_URI' => '/TvcCost/clientId/1/',
                    'REQUEST_METHOD' => 'POST',
                    'rawPostInput' => file_get_contents(__DIR__. DIRECTORY_SEPARATOR .  'data' . DIRECTORY_SEPARATOR .  'createTvcCost.json'),
                    'ENVIRONMENT' => 'test',
                );
                
                $config->test['request']['headers'] = array(
                    //'Authorization' => 'Complicated YWRhbS5zeW5ub3R0OkNyZWF0aXZlQ29kaW5nMTQ=',
                    'User-Agent' => 'Chrome/43.0.2357.124',
                    'Content-Type' => 'application/json',
                );
                $config->init(); // init the config again with new configs
                $app = new Application($config);

                $TvcCost = new TvcCost($app);
                $TvcCost->default_event($app->request());
                
                $data = $TvcCost->getAll();
                $this->assertTrue($data['status_code'] === 201);
                
                $location = explode('/', $data['locationUrl']);
                $id = end($location);
                $TvcCostModel = $app->model('TvcCost');
               
                $actualCostEntry = $TvcCostModel->getTvcCostById($id);
                
                foreach(array_keys($CostComparison) as $key) {
                    if (array_key_exists($key, $actualCostEntry)) {
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
    public function testGet()
    {   
        /* Retrieves a single tvc cost entry */
        $costComparison =  json_decode(file_get_contents(__DIR__.'/data/singleCostEntry.json'), true);
        
        try 
        {
            $config = new Config();

            $config->test['request']['server'] = array(
                'REQUEST_URI' => '/job/clientId/1/id/44',
                'ENVIRONMENT' => 'test',
            );

            $config->test['request']['headers'] = array(
                //'Authorization' => 'Complicated YWRhbS5zeW5ub3R0OkNyZWF0aXZlQ29kaW5nMTQ=',
                'User-Agent' => 'Chrome/43.0.2357.124',
            );
             
            
            $config->init(); // init the config again with new configs
            
            $app = new Application($config);
 
            $TvcCost = new TvcCost($app);
            $TvcCost->default_event($app->request());
            
            $data = $TvcCost->getAll();
            $this->assertTrue($data['status_code'] === 200);
                
            
            $actualCost = $TvcCost->handleGet($app->request());

            /* check if all the keys exist */
 
            foreach(array_keys($costComparison) as $key) {
                if (array_key_exists($key, $actualCost)) {
                    
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
        /* now test job collection (testing keys) */
        $costComparison =  json_decode(file_get_contents(__DIR__.'/data/singleCostEntry.json'), true);
        
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
            $TvcCost = new TvcCost($app);
            $TvcCost->default_event($app->request());
            
            $data = $TvcCost->getAll();
            $this->assertTrue($data['status_code'] === 200);
            
            $AllCostEntries = $TvcCost->handleGet($app->request());
            
            $randomCost = $AllCostEntries[rand(0,count($AllCostEntries))];      //select a random cost entry
            // check if all the keys exist
            foreach(array_keys($costComparison) as $key) {
                if (array_key_exists($key, $randomCost)) {
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
    
    public function testPatch()
    {
        /* now test job collection (testing keys) */
        $costComparison =  json_decode(file_get_contents(__DIR__.'/data/patchedCostEntry.json'), true);
        try 
        {
            $config = new Config();
            $config->test['request']['server'] = array(
                'REQUEST_URI' => '/TvcCost/clientId/1/id/44',
                'REQUEST_METHOD' => 'PATCH',
                'rawPostInput' => file_get_contents(__DIR__. DIRECTORY_SEPARATOR .  'data' . DIRECTORY_SEPARATOR .  'patchedCostEntry.json'),
                'ENVIRONMENT' => 'test',
            );
            
            $config->test['request']['headers'] = array(
                //'Authorization' => 'Complicated YWRhbS5zeW5ub3R0OkNyZWF0aXZlQ29kaW5nMTQ=',
                'User-Agent' => 'Chrome/43.0.2357.124',
                'Content-Type' => 'application/json',
            );
            $config->init(); // init the config again with new configs

            $app = new Application($config);
            $TvcCost = new TvcCost($app);
            $TvcCost->default_event($app->request());
            
            $data = $TvcCost->getAll();
            $this->assertTrue($data['status_code'] === 204);
            
            $TvcCostModel = $app->model('TvcCost');
            $patchedCostEntry = $TvcCostModel->getTvcCostById('44');      
            // check if all the keys exist
            foreach(array_keys($costComparison) as $key) {
                if (array_key_exists($key, $patchedCostEntry)) {
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
    
    public function testDelete(){
        
        //Creates an initial config, so the tvc model can be instantiated and then 
        //gets the last entered row. Reconfigures the config array and then reinitializes it
        //so a dynamic deletion can occur. 
        try 
        {
            $config = new Config();
            $config->test['request']['server'] = array(
                'REQUEST_URI' => '/TvcCost/clientId/1/id/44',
                'REQUEST_METHOD' => 'DELETE',
                'ENVIRONMENT' => 'test',
            );
            
            $config->test['request']['headers'] = array(
                //'Authorization' => 'Complicated YWRhbS5zeW5ub3R0OkNyZWF0aXZlQ29kaW5nMTQ=',
                'User-Agent' => 'Chrome/43.0.2357.124',
            );
            $config->init(); // init the config again with new configs

            $app = new Application($config);
            $TvcCostModel = $app->model('TvcCost');
            
            $finalId = $TvcCostModel->getLastId()['tco_id'];
            
            $config->test['request']['server'] = array(
                'REQUEST_URI' => '/TvcCost/clientId/1/id/' . $finalId,
            );
            
            $config->init();
            $app = new Application($config);
            $TvcCost = new TvcCost($app);
            $TvcCost->default_event($app->request());
            
            $data = $TvcCost->getAll();
            $this->assertTrue($data['status_code'] === 204);
            
            if (!$TvcCostModel->getTvcCostById($finalId)) {
                    $this->assertTrue(true);
            } else {
                    $this->assertTrue(false);
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
}