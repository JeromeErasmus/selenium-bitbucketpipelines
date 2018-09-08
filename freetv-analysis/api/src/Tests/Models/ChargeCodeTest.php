<?php

/**
 */

namespace App\Tests\Models;

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../../vendor/autoload.php';

use App\Models\ChargeCode as Model;
use App\Config\Config;
use Elf\Application\Application;

/**
 * Description of RoleTest
 *
 * @author michael
 */
class ChargeCodeTest extends \PHPUnit_Framework_TestCase {

    private $config;
    private $app;

    public function setUp() {
        $this->config = new Config();
        $this->config->test['request']['server'] = array(
            'REQUEST_URI' => '/chargeCode/clientId/1',
            'REQUEST_METHOD' => 'POST',
            'ENVIRONMENT' => 'test',
        );

        $this->config->test['request']['headers'] = array(
            'User-Agent' => 'Chrome/43.0.2357.124',
            'Content-Type' => 'application/json',
        );

        $this->config->init();
        $this->app = new Application($this->config);
        
        $sql = "DELETE FROM [dbo].[charge_codes] where cco_charge_code = 'ZF';"; // hard delete a soft deleted charge code for testing purposes #clean slate
        $sth = $this->app->db->prepare($sql);
        $sth->execute();
        
    }

    //put your code here
    public function testRetrieval() {
        $capsule = $this->app->service('eloquent')->getCapsule();
        $model = Model::find("B");

        $this->assertTrue($model->description === "BILLBOARD");
        $this->assertTrue($model->billingRate == "40.50");
        $this->assertTrue(is_array($model->getAsArray()));

        // check a non-existant entity
        $model = Model::find(-1);
        $this->assertFalse($model instanceof Model);
    }

    public function testCreationUpdationAndDeletion() {
        $id = 'ZF';
        $data = array(
            'id' => $id,
            'description' => 'testing Charge Codes',
            'billingRate' => 0.00,
            'typeCode' => 'J',
            'discount' => '10',
            'mr' => "X",
            'active' => true,
            'effectiveFrom' => '2015-09-09 00:00:00',
        );

        $model = new Model;
        $model->setFromArray($data);
        $this->assertTrue($model->save());

        $updateData = array(
            'description' => 'testing Charge Codes + Updated',
        );

        $updateModel = Model::find($id);

        $updateModel->setFromArray($updateData);


        $this->assertTrue($updateModel->save());

        $updateConfirmModel = Model::find($id);

        $confirmationData = $updateConfirmModel->getAsArray();

        $this->assertTrue($confirmationData['description'] === 'testing Charge Codes + Updated');

        $this->assertTrue(Model::destroy($id) === 1);
    }

    public function tearDown() {
        $this->app = null;
        $this->config = null;
    }

}
