<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Tests;

require_once __DIR__.'/../../../vendor/autoload.php';

use App\Config\Config;
use Elf\Application\Application;  // @todo replace this with Mock Application Elf\Mocks\Application\Application
use App\Services\Authentication;
use Elf\Exception\UnauthorizedException;

/**
 * Description of AuthenticationTest
 *
 * @author michael
 */
class NetworkAuthenticationTest extends \PHPUnit_Framework_TestCase 
{

    public function setUp()
    { 
        
            $this->config = new Config();

            $this->config->test['request']['server'] = array(
                'REQUEST_URI' => '/login/clientId/3/',
                'ENVIRONMENT' => 'test',
            );

            $this->config->test['request']['headers'] = array(
                'User-Agent' => 'Chrome/43.0.2357.124',
            );

            $this->config->init(); // init the config again with new configs

    }
    
    public function testAuthentication()
    {
        // test not implemented
        try {
            $app = new Application($this->config);       
            $authentication = new Authentication($app);
            $authentication->authenticateUser();
            $this->fail("Authorized while not implemented");
        } catch(\Exception $exception) {
            $this->assertTrue(true);
        }
       
    }

    public function tearDown()
    {
        $this->config = null;
    }
}
