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
class CadAuthenticationTest extends \PHPUnit_Framework_TestCase 
{

    public function setUp()
    { 
        
            $this->config = new Config();

            $this->config->test['request']['server'] = array(
                'REQUEST_URI' => '/login/clientId/1/',
                'ENVIRONMENT' => 'test',
            );

            $this->config->test['request']['headers'] = array(
                'User-Agent' => 'Chrome/43.0.2357.124',
            );

            $this->config->init(); // init the config again with new configs

    }
    
    public function testAuthentication()
    {
        // successul login
        try {
            $app = new Application($this->config);       
            $authentication = new Authentication($app);
            $authentication->authenticateUser();
            $this->assertTrue(true);
        } catch(UnauthorizedException $exception) {
            $this->fail('Unauthorized Access');
        }

        // change clientId
        $this->config->test['request']['server'] = array(
                'REQUEST_URI' => '/login/clientId/2/',
                'ENVIRONMENT' => 'test',
            );
        $this->config->init();
        
        try {
            $app = new Application($this->config);       
            $authentication = new Authentication($app);
            $authentication->authenticateUser();
            $this->fail("Authorized with invalid client Id");
        } catch(\Exception $exception) {
            // @todo update this once new authentication methods are implemented
            $this->assertTrue(true);
        }
        
        // no clientId
        $this->config->test['request']['server'] = array(
                'REQUEST_URI' => '/login/',
                'ENVIRONMENT' => 'test',
            );
        $this->config->init();
        try {
            $app = new Application($this->config);       
            $authentication = new Authentication($app);
            $authentication->authenticateUser();
            $this->fail("Authorized with no client Id");
        } catch(\Exception $exception) {
            // @todo update this once new authentication methods are implemented
            $this->assertTrue(true);
        }

        // unsuccessful login cad user
        $this->config->test['request']['server'] = array(
            'REQUEST_URI' => '/login/clientId/1/',
            'ENVIRONMENT' => 'test',
        );
        $this->config->test['request']['headers'] = array(
            'Authorization' => 'Complicated YWRhbS5zeW5ub3R0OkNyZWF0aXZlQ29kaW5nMTQ=',
            'User-Agent' => 'Chrome/43.0.2357.124',
        );
        $this->config->init();
        try {
            $app = new Application($this->config);       
            $authentication = new Authentication($app);
            $authentication->authenticateUser();
            $this->fail("Authorized with invalid user credentials");
        } catch(UnauthorizedException $exception) {
            $this->assertTrue(true);
        }

    }

    public function tearDown()
    {
        $this->config = null;
    }
    
}
