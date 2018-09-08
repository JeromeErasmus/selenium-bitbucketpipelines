<?php
/**
 * Created by PhpStorm.
 * User: jeremy.paul
 * Date: 24/08/2015
 * Time: 1:17 PM
 */
ini_set('display_errors',1);
error_reporting(E_ALL);

require_once __DIR__.'/../../../vendor/autoload.php';

use App\Config\Config;
use Elf\Application\Application;
use Elf\Exception\UnauthorizedException;
use App\Controllers\AgencyUser;


class AgencyUserTest extends PHPUnit_Framework_TestCase {

    public function testPost()
    {

        $agencyUserComparison = json_decode(file_get_contents(__DIR__.'/data/createAgencyUser.json'), true);

        try {
            $config = new Config();

            $config->test['request']['server'] = array(
                'REQUEST_URI' => '/AgencyUser/clientId/1',
                'REQUEST_METHOD' => 'POST',
                'rawPostInput' => file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'createAgencyUser.json'),
                'ENVIRONMENT' => 'test',
            );

            $config->test['request']['headers'] = array(
                'User-Agent' => 'Chrome/43.0.2357.124',
                'Content-Type' => 'application/json',
            );

            $config->init();
            $app = new Application($config);

            $AgencyUser = new AgencyUser($app);
            $AgencyUser->default_event($app->request());

            $data = $AgencyUser->getAll();

            $this->assertTrue($data['status_code'] === 201);

            $location = explode('/', $data['locationUrl']);
            $id = end($location);

            $agencyUserModel = $app->model('AgencyUser');
            $savedResult = $agencyUserModel->getAgencyUserById($id);

            foreach($agencyUserComparison as $key => $value) {

                if($key === 'password') {
                    continue;
                }
                
                if( $value != $savedResult[$key] ) {
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
        $agencyUserComparison = json_decode(file_get_contents(__DIR__.'/data/sampleRetrievedList.json'), true);

        try
        {
            $id = $this->retrieveLastInsertedId();
            $config = new Config();

            $config->test['request']['server'] = array(
                'REQUEST_URI' => '/AgencyUser/clientId/1/userId/' . $id,
                'ENVIRONMENT' => 'test',
            );

            $config->test['request']['headers'] = array(
                'User-Agent' => 'Chrome/43.0.2357.124',
            );
            $config->init();
            $app = new Application($config);

            $AgencyUser = new AgencyUser($app);
            $AgencyUser->default_event($app->request());

            $data = $AgencyUser->getAll();
            $retrievedData = $data['data'];
            $this->assertTrue($data['status_code'] === 200);

            foreach($agencyUserComparison as $key => $value) {
                //Because the saved JSON's id value remains static, avoid comparison with the Id's
               if($key == 'password') {
                   continue;
               }
               if ( $key != 'userId' && $key != 'agency' ) {
                    if( $value != $retrievedData[$key] ) {
                        $this->assertTrue(false);
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

    public function testPatch()
    {
        $agencyUserComparison = json_decode(file_get_contents(__DIR__ . '/data/samplePatchedList.json'), true);

        try {
            $id = $this->retrieveLastInsertedId();
            $config = new Config();

            $config->test['request']['server'] = array(
                'REQUEST_URI' => '/AgencyUser/clientId/1/userId/' . $id,
                'REQUEST_METHOD' => 'PATCH',
                'rawPostInput' => file_get_contents(__DIR__. DIRECTORY_SEPARATOR .  'data' . DIRECTORY_SEPARATOR .  'samplePatchedList.json'),
                'ENVIRONMENT' => 'test',
            );

            $config->test['request']['headers'] = array(
                'User-Agent' => 'Chrome/43.0.2357.124',
                'Content-Type' => 'application/json',
            );
            $config->init();

            $app = new Application($config);
            $agencyUser = new AgencyUser($app);
            $agencyUser->default_event($app->request());

            $data = $agencyUser->getAll();
            $this->assertTrue($data['status_code'] === 204);

            $agencyUserModel = $app->model('AgencyUser');
            $patchedResult = $agencyUserModel->getAgencyUserById($id);

            //Since a user can selectively change a few saved values, this part of the code uses the post data that was sent as a parameter
            //and then checks through the retrieved result to confirm that the same entries exist under the same keys
            foreach($agencyUserComparison as $key => $value) {
                if($key == 'password') {
                   continue;
                }              
                if (!array_key_exists($key,$patchedResult)) {
                    $this->assertTrue(false);
                } elseif ($value != $patchedResult[$key]) {
                    $this->assertTrue(false);
                }
            }
        }
        catch (UnauthorizedException $exception) {
            $this->fail($exception->getMessage());
        }
        catch (\Exception $exception) {
            $this->fail($exception->getMessage());
        }
    }

    public function testDelete()
    {
        try {
            $id = $this->retrieveLastInsertedId();
            $config = new Config();

            $config->test['request']['server'] = array(
                'REQUEST_URI' => '/AgencyUser/clientId/1/userId/' . $id,
                'REQUEST_METHOD' => 'DELETE',
                'ENVIRONMENT' => 'test',
            );

            $config->test['request']['headers'] = array(
                'User-Agent' => 'Chrome/43.0.2357.124',
                'Content-Type' => 'application/json',
            );
            $config->init();

            $app = new Application($config);
            $agencyUser = new AgencyUser($app);
            $agencyUser->default_event($app->request());

            $data = $agencyUser->getAll();
            $this->assertTrue($data['status_code'] === 204);

            $agencyUserModel = $app->model('AgencyUser');

            if(!$agencyUserModel->getAgencyUserById($id)) {
                $this->assertTrue(true);
            } else {
                $this->assertTrue(false);
            }
        }
        catch (UnauthorizedException $exception) {
            $this->fail($exception->getMessage());
        }
        catch (\Exception $exception) {
            $this->fail($exception->getMessage());
        }
    }

    public function testAgencyUserCollection()
    {
        $agencyUserComparison = json_decode(file_get_contents(__DIR__.'/data/sampleAgencyCollectionList.json'), true);

        try {
            $config = new Config();

            $config->test['request']['server'] = array(
                'REQUEST_URI' => 'AgencyUser/clientId/1/agencyId/94',
                'ENVIRONMENT' => 'test',
            );

            $config->test['request']['headers'] = array(
                'User-Agent' => 'Chrome/43.0.2357.124',
                'Content-Type' => 'application/json',
            );
            $config->init();

            $app = new Application($config);
            $agencyUser = new AgencyUser($app);
            $agencyUser->default_event($app->request());

            $data = $agencyUser->getAll();
            $retrievedData = $data['data'];

            $this->assertTrue($data['status_code'] === 200);

            foreach($agencyUserComparison as $key => $value) {
                if ($value != $retrievedData[$key]) {
                    $this->assertTrue(false);
                }
            }
        }
        catch (UnauthorizedException $exception) {
            $this->fail($exception->getMessage());
        }
        catch (\Exception $exception) {
            $this->fail($exception->getMessage());
        }
    }
    /*
     * Retrieves the most recently inserted row
     */
    public function retrieveLastInsertedId()
    {
        /// Dummy config so that the model can be initialized
        try
        {
            $config = new Config();


            $config->test['request']['server'] = array(
                'REQUEST_URI' => '/AgencyUser/clientId/1/userId/999',
                'ENVIRONMENT' => 'test',
            );

            $config->test['request']['headers'] = array(

                'User-Agent' => 'Chrome/43.0.2357.124',
            );
            $config->init(); // init the config again with new configs
            $app = new Application($config);

            $agencyUserModel = $app->model('AgencyUser');
            $lastInsertedId = $agencyUserModel->getLastId();

            return $lastInsertedId;
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
