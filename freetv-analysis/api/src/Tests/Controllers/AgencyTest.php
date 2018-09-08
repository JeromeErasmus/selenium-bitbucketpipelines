<?php
/**
 * Created by PhpStorm.
 * User: jeremy.paul
 * Date: 8/09/2015
 * Time: 4:44 PM
 */

ini_set('display_errors',1);
error_reporting(E_ALL);

require_once __DIR__.'/../../../vendor/autoload.php';

use App\Config\Config;
use Elf\Application\Application;
use Elf\Exception\UnauthorizedException;
use App\Controllers\Agency;


class AgencyTest extends PHPUnit_Framework_TestCase {

    public function testCollection()
    {
        $agencyFilters = json_decode(file_get_contents(__DIR__.'/data/sampleAgencyCollectionFilters.json'), true);
        $getParameters = '';
        foreach($agencyFilters as $key => $value) {
            $getParameters .= '/' . $key . '/' . str_replace(' ', '+',$value);
        }

        try {
            $config = new Config();
            $config->test['request']['server'] = array(
                'REQUEST_URI' => '/Agency' . $getParameters,
                'ENVIRONMENT' => 'test',
            );

            $config->test['request']['headers'] = array(
                'User-Agent' => 'Chrome/43.0.2357.124',
            );


            $config->init(); // init the config again with new configs
            $app = new Application($config);
            $agencyController = new Agency($app);
            $agencyCollection = $agencyController->handleGet($app->request());

            $data = $agencyController->getAll();
            $this->assertTrue($data['status_code'] === 200);

            foreach($agencyCollection as $agencyIndex => $agency) {
                foreach($agency as $key => $value) {
                    if (array_key_exists($key,$agencyFilters)) {
                        if ($value == $agencyFilters[$key]) {
                            $this->assertTrue(true);
                        } else {
                            $this->assertTrue(false);
                        }
                    }
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

    public function testGetById()
    {
        $agencyComparison = json_decode(file_get_contents(__DIR__.'/data/sampleAgencyCollectionEntry.json'), true);

        try {
            $config = new Config();
            $config->test['request']['server'] = array(
                'REQUEST_URI' => '/Agency/agencyId/24633',
                'ENVIRONMENT' => 'test',
            );

            $config->test['request']['headers'] = array(
                'User-Agent' => 'Chrome/43.0.2357.124',
            );

            $config->init(); // init the config again with new configs
            $app = new Application($config);
            $agencyController = new Agency($app);
            $agencyData = $agencyController->handleGet($app->request());

            $data = $agencyController->getAll();
            $this->assertTrue($data['status_code'] === 200);

            foreach(array_keys($agencyComparison) as $key) {
                if (array_key_exists($key, $agencyData)) {
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

    public function testGetByName()
    {
        $agencyComparison = json_decode(file_get_contents(__DIR__.'/data/sampleAgencyCollectionEntry.json'), true);

        try {
            $config = new Config();
            $config->test['request']['server'] = array(
                'REQUEST_URI' => '/agency/clientId/1/agencyName/27DC',
                'ENVIRONMENT' => 'test',
            );

            $config->test['request']['headers'] = array(
                'User-Agent' => 'Chrome/43.0.2357.124',
            );

            $config->init(); // init the config again with new configs
            $app = new Application($config);
            $agencyController = new Agency($app);
            $agencyData = $agencyController->handleGet($app->request());

            $data = $agencyController->getAll();
            $this->assertTrue($data['status_code'] === 200);

            if(count($agencyData) > 0) { //unwrap the result    
                $agencyData = $agencyData[0];
            }
            
            foreach(array_keys($agencyComparison) as $key) {
                if (array_key_exists($key, $agencyData)) {
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

    public function testPost()
    {
        $agencyComparison = json_decode(file_get_contents(__DIR__.'/data/sampleAgencyCollectionEntry.json'), true);

        //The database requires a custom agency code to successfully save, this code generates a unique agency code

        $agencyPostData = json_decode(file_get_contents(__DIR__.'/data/sampleAgencyPostData.json'),true);
        $agencyPostData['agencyCode'] = substr(str_shuffle("AA1HXInAcyhbmfeB05OjixoyVn4AOR"),0,7);

        $agencyPostData = json_encode($agencyPostData);

        try {
            $config = new Config();
            $config->test['request']['server'] = array(
                'REQUEST_URI' => '/Agency/',
                'REQUEST_METHOD' => 'POST',
                'rawPostInput' => $agencyPostData,
                'ENVIRONMENT' => 'test',
            );

            $config->test['request']['headers'] = array(
                'User-Agent' => 'Chrome/43.0.2357.124',
                'Content-Type' => 'application/json',
            );

            $config->init(); // init the config again with new configs
            $app = new Application($config);

            $agencyController = new Agency($app);
            
            
            
            $agencyController->handlePost($app->request());

            $data = $agencyController->getAll();

            $this->assertTrue($data['status_code'] === 201);
            $location = explode('/', $data['locationUrl']);
            $agencyId = end($location);
            
            

            $agencyModel = new \App\Models\Agency($app);
            $agencyModel->getAgencyById($agencyId);
            $agencyData = $agencyModel->getAsArray();

            foreach(array_keys($agencyComparison) as $key) {
                if (array_key_exists($key, $agencyData)) {
                    $this->assertTrue(true);
                } else {
                    $this->assertTrue(false);
                }
            }

            $agencyModel->setAgencyId($agencyId);
            $agencyModel->deleteAgency();

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
        $id = 25215;
        $agencyPatchData = json_decode(file_get_contents(__DIR__.'/data/sampleAgencyPatchData.json'), true);

        foreach ($agencyPatchData as $parameter => $value) {
            if (gettype($value) == 'string' ) {
                $agencyPatchData[$parameter] = substr(md5(rand()), 0, 3);
            } elseif (gettype($value) == 'boolean') {
                $agencyPatchData[$parameter] = true ? true : false;
            }
        }

        try {
            $config = new Config();
            $config->test['request']['server'] = array(
                'REQUEST_URI' => '/Agency/clientId/clientId/agencyId/'.$id,
                'REQUEST_METHOD' => 'POST',
                'rawPostInput' => json_encode($agencyPatchData),
                'ENVIRONMENT' => 'test',
            );

            $config->test['request']['headers'] = array(
                'User-Agent' => 'Chrome/43.0.2357.124',
                'Content-Type' => 'application/json',
            );

            $config->init(); // init the config again with new configs
            $app = new Application($config);

            $agencyController = new Agency($app);
            $agencyController->handlePatch($app->request());

            $data = $agencyController->getAll();
            $this->assertTrue($data['status_code'] === 204);

            $agencyModel = new \App\Models\Agency($app);
            $agencyModel->getAgencyById($id);
            $agencyData = $agencyModel->getAsArray(true);

            //Check to see if the data in the patch exists in the model
            foreach($agencyPatchData as $key => $value)
            {
                $this->assertTrue($agencyData[$key] == $value);
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
