<?php
/**
 * Created by PhpStorm.
 * User: jeremy.paul
 * Date: 28/09/2015
 * Time: 11:12 AM
 */
ini_set('display_errors',1);
error_reporting(E_ALL);

require_once __DIR__.'/../../../vendor/autoload.php';

use App\Config\Config;
use Elf\Application\Application;
use Elf\Exception\UnauthorizedException;
use App\Controllers\KeyNumber;

class KeyNumberTest extends \PHPUnit_Framework_TestCase {

    public function testGetByID()
    {
        $keyNumberComparison = json_decode(file_get_contents(__DIR__.'\data\KeyNumber\sampleKeyNumberEntry.json'), true);

        try {
            $config = new Config();
            $config->test['request']['server'] = array(
                'REQUEST_URI' => '/KeyNumber/id/4',
                'ENVIRONMENT' => 'test',
            );

            $config->test['request']['headers'] = array(
                'User-Agent' => 'Chrome/43.0.2357.124',
            );

            $config->init(); // init the config again with new configs
            $app = new Application($config);
            $keyNumberController = new KeyNumber($app);
            $keyNumberData = $keyNumberController->handleGet($app->request());

            $data = $keyNumberController->getAll();
            $this->assertTrue($data['status_code'] === 200);

            foreach(array_keys($keyNumberComparison) as $key) {
                if (array_key_exists($key, $keyNumberData)) {
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
        $keyNumberComparison = json_decode(file_get_contents(__DIR__.'/data/KeyNumber/sampleKeyNumberPostData.json'), true);
        unset($keyNumberComparison['cadNumber']);
        unset($keyNumberComparison['advertiserId']);

        try {
            $config = new Config();
            $config->test['request']['server'] = array(
                'REQUEST_URI' => '/keyNumber/clientId/1',
                'REQUEST_METHOD' => 'POST',
                'rawPostInput' => file_get_contents(__DIR__. DIRECTORY_SEPARATOR .  'data' . DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR .  'KeyNumber' . DIRECTORY_SEPARATOR .  'sampleKeyNumberPostData.json'),
                'ENVIRONMENT' => 'test',
            );

            $config->test['request']['headers'] = array(
                'User-Agent' => 'Chrome/43.0.2357.124',
                'Content-Type' => 'application/json',
            );

            $config->init(); // init the config again with new configs
            $app = new Application($config);

            $keyNumberController = new KeyNumber($app);
            $keyNumberController->default_event($app->request());

            $data = $keyNumberController->getAll();
            
            $this->assertTrue($data['status_code'] === 201);

            $location = explode('/', $data['locationUrl']);
            $tvcId = end($location);

            $keyNumberModel = new \App\Models\KeyNumber($app);
            $keyNumberModel->getTvcById($tvcId);
            $keyNumberData = $keyNumberModel->getAsArray();

            foreach(array_keys($keyNumberComparison) as $key) {

                if (array_key_exists($key, $keyNumberData) && ($keyNumberComparison[$key] == $keyNumberData[$key])) {
                    $this->assertTrue(true);
                } else {
                    $this->assertTrue(false);
                }
            }
            // Delete the tvc
            $keyNumberModel->setTvcId($tvcId);
            $keyNumberModel->deleteTvc();
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
        $id = 1037274;
        $keyNumberPatchData = json_decode(file_get_contents(__DIR__.'/data/KeyNumber/sampleKeyNumberPatchData.json'), true);

        //Randomly set the description every time, the test further down checks to see if the random description is still there
        foreach ($keyNumberPatchData as $parameter => $value) {
            if ($parameter == 'description' ) {
             //   $keyNumberPatchData[$parameter] = substr(md5(rand()), 0, 6);
            }
        }

        try {
            $config = new Config();
            $config->test['request']['server'] = array(
                'REQUEST_URI' => '/keyNumber/clientId/1/id/'.$id,
                'REQUEST_METHOD' => 'PATCH',
                'rawPostInput' => json_encode($keyNumberPatchData),
                'ENVIRONMENT' => 'test',
            );

            $config->test['request']['headers'] = array(
                'User-Agent' => 'Chrome/43.0.2357.124',
                'Content-Type' => 'application/json',
            );

            $config->init(); // init the config again with new configs
            $app = new Application($config);

            $keyNumberController = new KeyNumber($app);
            $keyNumberController->handlePatch($app->request());

            $data = $keyNumberController->getAll();
           
            $this->assertTrue($data['status_code'] === 204);

            $keyNumberModel = new \App\Models\KeyNumber($app);
            $keyNumberModel->getTvcById($id);
            $keyNumberData = $keyNumberModel->getAsArray(true);

            foreach($keyNumberData as $key => $value)
            {
                $this->assertTrue($keyNumberData[$key] == $value);
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
