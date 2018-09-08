<?php
/**
 */
namespace App\Tests\Models;

ini_set('display_errors',1);
error_reporting(E_ALL);

require_once __DIR__.'/../../../vendor/autoload.php';

use App\Models\Role;
use App\Config\Config;
use Elf\Application\Application;

/**
 * Description of RoleTest
 *
 * @author michael
 */
class RoleTest extends \PHPUnit_Framework_TestCase 
{
    
    private $config;
    private $app;
    
    public function setUp()
    {
        $this->config = new Config();
        $this->config->test['request']['server'] = array(
            'REQUEST_URI' => '/AgencyUser/clientId/1',
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
    public function testRoleRetrieval()
    {

        $role = new Role($this->app);
        $role->setRoleId(200);
        $role->load();

        $this->assertTrue($role->getRoleName() === "CAD");
        $this->assertTrue($role->getRoleSlug() === "/jobList/");
        $this->assertTrue(is_array($role->getRolePermissionSet()));
        
        // check a non-existant entity
        try {
            $role->setRoleId(-1);
            $role->load();
            $this->fail("loaded invalid role");
        } catch(\Exception $exception) {
            $this->assertTrue(true);
        }
    }
    
    public function testRoleCreationUpdationAndDeletion()
    {
        
        $roleData = array(
            'role_name' => 'testing Roles',
            'role_slug' => '/roles/test',
            'role_permission_set' => json_encode(array('test' => 'test')),
        );
        
        $role = new Role($this->app);
        $role->setFromArray($roleData);
        try {
            $role->save();
            $this->assertTrue(true);
        } catch(\Exception $exception) {
            $this->fail("Role Model not persisted: " . $exception->getMessage());
        }
        
        $id = $role->getRoleId();
        
        $role->setRoleId($id);
        $role->load();
        $roleData['role_slug'] = '/roles/updated';
        $permissions = $role->getRolePermissionSet();
        $permissions['test'] = 'updated';
        $roleData['role_permission_set'] = $permissions;
        $role->setFromArray($roleData);
        $role->save();
        
        $role->setRoleId($id);
        $role->load();
        $this->assertTrue($role->getRoleSlug() === '/roles/updated');
        $permissions = $role->getRolePermissionSet();
        $this->assertTrue($permissions['test'] === 'updated');
        
        $role->deleteById($id);
        
        try {
            $role->setRoleId($id);
            $role->load();
            $this->fail("Role not correctly deleted");
        } catch(\Exception $exception) {
            $this->assertTrue(true);
        }
        
    }

    public function tearDown()
    {
        $this->app = null;
        $this->config = null;
    }
    
}
