<?php
/**
 * Created by PhpStorm.
 * User: jeremy.paul
 * Date: 26/10/2015
 * Time: 1:59 PM
 */

namespace App\Tests\Models;

ini_set('display_errors',1);
error_reporting(E_ALL);

require_once __DIR__.'/../../../vendor/autoload.php';

use App\Models\Network as Model;
use App\Config\Config;
use Elf\Application\Application;

class NetworkTest extends \PHPUnit_Framework_TestCase {

    private $config;
    private $app;

    public function setUp()
    {
        $this->config = new Config();
        $this->config->test['request']['server'] = array(
            'REQUEST_URI' => '/country/clientId/1',
            'REQUEST_METHOD' => 'POST',
            'ENVIRONMENT' => 'test',
        );

        $this->config->test['request']['headers'] = array(
            'User-Agent' => 'Chrome/43.0.2357.124',
            'Content-Type' => 'application/json',
        );

        $this->config->init();
        $this->app = new Application($this->config);
    }



    //put your code here
    public function testRetrieval()
    {
        $capsule = $this->app->service('eloquent')->getCapsule();
        $model = Model::find(1);

        $this->assertTrue($model->networkCode === "TSTCODE");
        $this->assertTrue($model->networkName === "test network name");
        $this->assertTrue(is_array($model->getAsArray()));

        // check a non-existant entity
        $model = Model::find(-1);
        $this->assertFalse($model instanceof Model);

    }

    public function testCreationUpdationAndDeletion()
    {

        $data = array(
            'networkCode' => 'network of testing',
            'networkName' => 'name of testing',
        );

        $model = new Model;
        $model->setFromArray($data);
        $this->assertTrue($model->save());


        $id = $model->id;

        $data = array(
            'networkName' => 'he who networks',
        );

        $model->setFromArray($data);
        $this->assertTrue($model->save());

        $model = Model::find($id);

        $data = $model->getAsArray();

        $this->assertTrue($data['networkName'] === 'he who networks');

        $this->assertTrue(Model::destroy($id) === 1);

    }

    public function tearDown()
    {
        $this->app = null;
        $this->config = null;
    }

}
