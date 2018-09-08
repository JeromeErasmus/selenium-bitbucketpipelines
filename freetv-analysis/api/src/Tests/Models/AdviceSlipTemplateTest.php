<?php
/**
 * Created by PhpStorm.
 * User: jeremy.paul
 * Date: 30/10/2015
 * Time: 2:23 PM
 */

namespace Models;

ini_set('display_errors',1);
error_reporting(E_ALL);

require_once __DIR__.'/../../../vendor/autoload.php';

use App\Models\AdviceSlipTemplate as Model;
use App\Config\Config;
use Elf\Application\Application;

class AdviceSlipTemplateTest extends \PHPUnit_Framework_TestCase {

    private $config;
    private $app;

    public function setUp()
    {
        $this->config = new Config();
        $this->config->test['request']['server'] = array(
            'REQUEST_URI' => '/adviceSlipTemplate/clientId/1',
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
        $model = Model::find(2);

        $this->assertTrue($model->adviceTemplate === "template template template ");
        $this->assertTrue(is_array($model->getAsArray())); 

        // check a non-existant entity
        $model = Model::find(-1);
        $this->assertFalse($model instanceof Model);

    }

    public function testCreationUpdationAndDeletion()
    {

        $now = new \DateTime();
        $data = array(
            'adviceTemplate' => 'test template',
        );

        $model = new Model;
        $model->setFromArray($data);
        $this->assertTrue($model->save());


        $id = $model->id;

        $data = array(
            'adviceTemplate' => 'test template patchco patcherson',
        );

        $model->setFromArray($data);
        $this->assertTrue($model->save());

        $model = Model::find($id);

        $data = $model->getAsArray();

        $this->assertTrue($data['adviceTemplate'] === 'test template patchco patcherson');

        $this->assertTrue(Model::destroy($id) === 1);

    }

    public function tearDown()
    {
        $this->app = null;
        $this->config = null;
    }

}
