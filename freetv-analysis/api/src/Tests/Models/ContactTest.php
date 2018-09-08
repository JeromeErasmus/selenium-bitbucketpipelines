<?php
/**
 * Created by PhpStorm.
 * User: jeremy.paul
 * Date: 14/10/2015
 * Time: 12:54 PM
 */

namespace App\Tests\Models;

ini_set('display_errors',1);
error_reporting(E_ALL);

require_once __DIR__.'/../../../vendor/autoload.php';

use App\Models\Contact as Model;
use App\Collections\ContactList;
use App\Config\Config;
use Elf\Application\Application;
use Elf\Exception\NotFoundException;

class ContactTest extends \PHPUnit_Framework_TestCase {
    private $config;
    private $app;

    public function setUp()
    {
        $this->config = new Config();
        $this->config->test['request']['server'] = array(
            'REQUEST_URI' => '/contact/clientId/1',
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
        $requiredKeys = json_decode(file_get_contents(__DIR__.'/data/agencycontact.keys.json'), true);
        $contactListModel = new ContactList($this->app);
        $agencyContacts = $contactListModel->getAll();

        foreach ($requiredKeys as $key) {
            $this->assertArrayHasKey($key, $agencyContacts[rand(0,count($agencyContacts)-1)] );
        }
    }

    public function testSingleContact()
    {
       
        $capsule = $this->app->service('eloquent')->getCapsule();
        $model = Model::find(1);

        $this->assertTrue($model->name === "Jeremy");
        $this->assertTrue($model->email === "jeremy@email.com");
        $this->assertTrue(is_array($model->getAsArray()));

        $model = Model::find(-1);
        $this->assertFalse($model instanceof Model);
    }

    public function testCreateUpdateDelete()
    {
        
        $data = array(
            'name' => 'Unit Test Name',
            'email' => 'UnitTestEmail@unittest.com',
            'notificationType' => '1',
            'active' => '1',
            'contactableId' => '27',
            'contactableType' => 'Agencies',
        );

        $model = new Model;
        $model->setFromArray($data);
        $this->assertTrue($model->save());

        $id = $model->id;

        $data = array(
            'name' => 'Unit Test Name Updated',
        );

        $model->setFromArray($data);
        $this->assertTrue($model->save());

        $model = Model::find($id);

        $data = $model->getAsArray();

        $this->assertTrue($data['name'] === 'Unit Test Name Updated');

        $this->assertTrue(Model::destroy($id) === 1);
    }

    public function tearDown()
    {
        $this->app = null;
        $this->config = null;
    }
}
