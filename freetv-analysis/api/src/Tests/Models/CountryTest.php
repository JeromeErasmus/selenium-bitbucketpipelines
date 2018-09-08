<?php
/**
 */
namespace App\Tests\Models;

ini_set('display_errors',1);
error_reporting(E_ALL);

require_once __DIR__.'/../../../vendor/autoload.php';

use App\Models\Country as Model;
use App\Config\Config;
use Elf\Application\Application;

/**
 * Description of RoleTest
 *
 * @author michael
 */
class CountryTest extends \PHPUnit_Framework_TestCase 
{
    
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
 
        $this->assertTrue($model->name === "Australia");
        $this->assertTrue($model->internalName === "Australia");
        $this->assertTrue(is_array($model->getAsArray()));
        
        // check a non-existant entity
        $model = Model::find(-1);
        $this->assertFalse($model instanceof Model);

    }
    
    public function testCreationUpdationAndDeletion()
    {
 
        $data = array(
            'name' => 'testing Country',
            'internalName' => 'testing Country + Internal Name',
            'code' => 'TFC',
        );
        
        $model = new Model;
        $model->setFromArray($data);
        $this->assertTrue($model->save());
        
        
        $id = $model->id;

        $data = array(
            'name' => 'testing Country + Updated',
        );
        
        $model->setFromArray($data);
        $this->assertTrue($model->save());
        
        $model = Model::find($id);
        
        $data = $model->getAsArray();
        
        $this->assertTrue($data['name'] === 'testing Country + Updated');
        
        $this->assertTrue(Model::destroy($id) === 1);
        
    }

    public function tearDown()
    {
        $this->app = null;
        $this->config = null;
    }
    
}
