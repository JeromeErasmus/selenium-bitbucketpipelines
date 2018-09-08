<?php
/**
 */
namespace App\Tests\Models;

ini_set('display_errors',1);
error_reporting(E_ALL);

require_once __DIR__.'/../../../vendor/autoload.php';

use App\Models\Requirement as Model;
use App\Config\Config;
use Elf\Application\Application;

/**
 * Description of Requirement Test
 *
 * @author shirleen
 */
class Requirement extends \PHPUnit_Framework_TestCase
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

        $Model = Model::find(92710);
        $data = $Model->getAsArray();

        $this->assertTrue($Model != null);
        $this->assertTrue(is_array($data));
        $this->assertTrue($data['jobId'] === "103");

        // check a non-existant entity
        $Model = Model::find(-1);
        $this->assertFalse($Model instanceof Model);
    }

    public function testCreationUpdationAndDeletion()
    {
        $data = array(
            "jobId" => "6667",
             "tvcId" => "20000",
            "referenceNo" => "1234",
             "agencyNotes" => "",
             "stationNotes" => "Waiting on amended vision.",
             "internalNotes" => "",
             "activityReportVisible"=> "",
             "description"=> "TEST",
             "createdAt"=> "2008-11-18 22:14:36.393",
             "createdBy"=> "Warne",
        );

        $Model = new Model;
        $Model->setFromArray($data);
        $this->assertTrue($Model->save());

        $id = $Model->req_id;

        $testData = array(
            'stationNotes' => 'TestNotes'
        );

        $Model->setFromArray($testData);
        $this->assertTrue($Model->save());
        $Model = Model::where('req_id', $id)->firstOrFail();
        $data = $Model->getAsArray();

        $this->assertTrue(($data['stationNotes'] === 'TestNotes'));

        $this->assertTrue(Model::destroy($id) === 1);
    }
/*
    public function tearDown()
    {
        $this->app = null;
        $this->config = null;
    }
*/
}
