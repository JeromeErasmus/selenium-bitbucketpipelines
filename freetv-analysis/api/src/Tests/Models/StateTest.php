<?php
/**
 */
namespace App\Tests\Models;

ini_set('display_errors',1);
error_reporting(E_ALL);

require_once __DIR__.'/../../../vendor/autoload.php';

use App\Models\State as Model;
use App\Config\Config;
use Elf\Application\Application;

/**
 * Description of RoleTest
 *
 * @author michael
 */
class StateTest extends \PHPUnit_Framework_TestCase 
{
    
    private $config;
    private $app;
    
    public function setUp()
    {
        $this->config = new Config();
        $this->config->test['request']['server'] = array(
            'REQUEST_URI' => '/state/clientId/1',
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
 
        $this->assertTrue($model->name === "NSW");
        $this->assertTrue($model->internalName === "NSW");
        $this->assertTrue(is_array($model->getAsArray()));
        
        // check a non-existant entity
        $model = Model::find(-1);
        $this->assertFalse($model instanceof Model);

    }
    
    public function testCreationUpdationAndDeletion()
    {
 
        $data = array(
            'name' => 'testing state',
            'internalName' => 'testing State + Internal Name',
            'code' => 'TFC',
            'needPostcode' => '0',
        );
        
        $model = new Model;
        $model->setFromArray($data);
        $this->assertTrue($model->save());
        
        
        $id = $model->id;

        $data = array(
            'name' => 'testing State + Updated',
        );
        
        $model->setFromArray($data);
        $this->assertTrue($model->save());
        
        $model = Model::find($id);
        
        $data = $model->getAsArray();
        
        $this->assertTrue($data['name'] === 'testing State + Updated');
        
        $this->assertTrue(Model::destroy($id) === 1);
        
    }

    public function tearDown()
    {
        $this->app = null;
        $this->config = null;
    }
    
}
