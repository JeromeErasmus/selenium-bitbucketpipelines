<?php
/**
 * Created by PhpStorm.
 * User: jeremy.paul
 * Date: 15/01/2016
 * Time: 11:05 AM
 */
namespace App\Tests\Models;

ini_set('display_errors',1);
error_reporting(E_ALL);

require_once __DIR__.'/../../../vendor/autoload.php';

use App\Collections\JobAlertList;
use App\Models\JobAlert as Model;
use App\Collections\ContactList;
use App\Config\Config;
use Elf\Application\Application;
use Elf\Exception\NotFoundException;

class JobAlertTest extends \PHPUnit_Framework_TestCase {
    private $config;
    private $app;

    public function setUp()
    {
        $this->config = new Config();
        $this->config->test['request']['server'] = array(
            'REQUEST_URI' => '/JobAlert/clientId/1',
            'REQUEST_METHOD' => 'GET',
            'ENVIRONMENT' => 'test',
        );

        $this->config->test['request']['headers'] = array(
            'Content-Type' => 'application/json',
        );

        $this->config->init();
        $this->app = new Application($this->config);
    }

    public function testContactCollection()
    {
        $requiredKeys = json_decode(file_get_contents(__DIR__.'/data/jobalert.keys.json'), true);
        $jobAlertListModel = new JobAlertList($this->app);
        $jobAlerts = $jobAlertListModel->getAll();

        foreach ($requiredKeys as $key) {
            $this->assertArrayHasKey($key, $jobAlerts[rand(0,count($jobAlerts)-1)] );
        }
    }

    public function testSingleAlert()
    {

        $capsule = $this->app->service('eloquent')->getCapsule();
        $model = Model::find(1);

        $this->assertTrue($model->alertMessage === "this is an alert");
        $this->assertTrue($model->alertSourceUserId === "7212");
        $this->assertTrue(is_array($model->getAsArray()));

        $model = Model::find(-1);
        $this->assertFalse($model instanceof Model);
    }

    public function testCreateUpdateDelete()
    {

        $data = array(
            'alertMessage' => 'Alert Unit Test',
            'alertDestinationUserId' => '7273',
            'alertSourceUserId' => '7147',
            'alertReadStatus' => '1',
            'jobId' => '1041035'
        );

        $model = new Model;
        $model->setFromArray($data);
        $this->assertTrue($model->save());

        $id = $model->id;

        $data = array(
            'alertMessage' => 'Unit Test Alert Updated',
        );

        $model->setFromArray($data);
        $this->assertTrue($model->save());

        $model = Model::find($id);

        $data = $model->getAsArray();

        $this->assertTrue($data['alertMessage'] === 'Unit Test Alert Updated');

        $this->assertTrue(Model::destroy($id) === 1);
    }

    public function tearDown()
    {
        $this->app = null;
        $this->config = null;
    }
}
