<?php
/**
 */
namespace App\Tests\Models;

ini_set('display_errors',1);
error_reporting(E_ALL);

require_once __DIR__.'/../../../vendor/autoload.php';

use App\Models\NetworkUser;
use App\Config\Config;
use Elf\Application\Application;

/**
 * Description of RoleTest
 *
 * @author michael
 */
class NetworkUserTest extends \PHPUnit_Framework_TestCase 
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
    public function testNetworkUserRetrieval()
    {

        $user = new NetworkUser($this->app);
        $user->setNetworkUserId(1);
        $user->load();
        
        $this->assertTrue($user->getSystemId() === 7647);
        $this->assertTrue($user->getFirstName() === "thimira");
        
        // check a non-existant entity
        try {
             $user->setNetworkUserId(-1);
            $user->load();
            $this->fail("loaded invalid role");
        } catch(\Exception $exception) {
            $this->assertTrue(true);
        }
    }
    
    public function testNetworkUserCreationUpdationAndDeletion()
    {
        
        $networkUserData = array(
            'agencyId' => 150, 
            'networkId' => 2, 
            'firstName' => 'Test', 
            'lastName' => 'Tester Lastname', 
            'email' => 'test+test@test.com', 
            'password' => '123password', 
            'active' => true,
        );
        
        $networkUser = new NetworkUser($this->app);
        $networkUser->setFromArray($networkUserData);
       
        try {
            $validates = $networkUser->validate($networkUser->getAsArray());
            $this->assertTrue($validates);
            $networkUserId = $networkUser->save();
            $this->assertTrue($networkUserId > 0);
        } catch(\Exception $exception) {
            $this->fail("Network User Model not persisted: " . $exception->getMessage());
        }

        $networkUser->setNetworkUserId($networkUserId);
        $networkUser->load();
        $networkUserData['first_name'] = 'Adam + updated';
        $networkUser->setFromArray($networkUserData);
        
        $networkUser->save();
        
        $networkUser->setNetworkUserId($networkUserId);
        $networkUser->load();
        
        $this->assertTrue($networkUser->getFirstName() === 'Adam + updated');
        
        $networkUser->deleteById($networkUserId);
        
        try {
            $networkUser->setNetworkUserId($networkUserId);
            $networkUser->load();
            $this->fail("Network User not correctly deleted");
        } catch(\Elf\Exception\NotFoundException $exception) {
            $this->assertTrue(true);
        }         
    }

    public function tearDown()
    {
        $this->app = null;
        $this->config = null;
    }
    
}
