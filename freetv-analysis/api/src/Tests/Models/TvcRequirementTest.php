<?php
/**
 */
namespace App\Tests\Models;

ini_set('display_errors',1);
error_reporting(E_ALL);

require_once __DIR__.'/../../../vendor/autoload.php';

use App\Models\TvcRequirement as Model;
use App\Config\Config;
use Elf\Application\Application;

/**
 * testTvcRequirementTest.php
 *
 * @author Jeremy
 */
class TvcRequirement extends \PHPUnit_Framework_TestCase
{

    private $config;
    private $app;

    public function setUp()
    {
        $this->config = new Config();
        $this->config->test['request']['server'] = array(
            'REQUEST_URI' => '/requirement/clientId/1/',
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

    public function testRetrieval()
    {
        $capsule = $this->app->service('eloquent')->getCapsule();
        $model = Model::find(1);
        $data = $model->getAsArray();

        $this->assertTrue($model != null);
        $this->assertTrue(is_array($data));
        $this->assertTrue($data['reqId'] === "1");
        $this->assertTrue($data['jobId'] === "110327");

        $model = model::find(-1);
        $this->assertFalse($model instanceof model);
    }

    public function testCreationUpdationAndDeletion()
    {
        $id = rand(1, 100);

        $data = array(
            "reqId" => $id,
            "tvcId" => "20000",
            "jobId" => "6667",
             "referenceNo" => "163",
             "keyNo" => "21"
        );

        $model = new Model();
        $model->setFromArray($data);
        $this->assertTrue($model->save());
        $data = $model->getAsArray();

        $createdId = $data["id"];

        $model->destroy($createdId);
    }
}
