<?php
/**
 * Created by PhpStorm.
 * User: shirleen.sharma
 * Date: 29/10/15
 * Time: 14:20
 */

namespace App\Tests\Models;

ini_set('display_errors',1);
error_reporting(E_ALL);

require_once __DIR__.'/../../../vendor/autoload.php';

use App\Models\RequirementsTemplate as Model;
use App\Config\Config;
use Elf\Application\Application;


class RequirementsTemplateTest extends \PHPUnit_Framework_TestCase
{

    private $config;
    private $app;

    public function setUp()
    {
        $this->config = new Config();
        $this->config->test['request']['server'] = array(
            'REQUEST_URI' => '/requirementsTemplate/clientId/1',
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

        $this->assertTrue($model->code === "13Char");
        $this->assertTrue($model->active === "1");
        $this->assertTrue(is_array($model->getAsArray()));

        // check a non-existant entity
        $model = Model::find(-1);
        $this->assertFalse($model instanceof Model);

    }

    public function testCreationUpdationAndDeletion()
    {

        $data = array(
            'code' => 'TESTEST',
            'active' => '0',
            'mandatory' => '0',
            'showOnDailyReport' => '0',
            'showOnCADSlip' => '0',
        );

        $model = new Model;
        $model->setFromArray($data);
        $this->assertTrue($model->save());


        $id = $model->id;

        $data = array(
            'code' => 'TESTEST + Updated',
        );

        $model->setFromArray($data);
        $this->assertTrue($model->save());

        $model = Model::find($id);

        $data = $model->getAsArray();

        $this->assertTrue($data['code'] === 'TESTEST + Updated');

        $this->assertTrue(Model::destroy($id) === 1);

    }

    public function tearDown()
    {
        $this->app = null;
        $this->config = null;
    }

}
