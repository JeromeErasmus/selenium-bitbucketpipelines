<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

ini_set('display_errors',1);
error_reporting(E_ALL);

require_once __DIR__.'/../../../vendor/autoload.php';

use App\Config\Config;
use Elf\Application\Application;
use Elf\Exception\UnauthorizedException;
use Elf\Exception\NotFoundException;
use App\Controllers\Login;
use App\Models\User;


class LoginTest extends \PHPUnit_Framework_TestCase
{

    public function testAdsLoginPass()
    {
        $loginComparison = json_decode(file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'loginExpectedReturn.json'), true);

        try {
            $username = "freetv";
            $password = "Password1!";
            $auth = base64_encode("$username:$password");

            $config = new Config();

            $config->test['request']['server'] = array(
                'REQUEST_URI' => '/login/clientId/1/',
                'ENVIRONMENT' => 'test',
            );

            $config->test['request']['headers'] = array(
                'Authorization' => 'Basic ' . $auth,
                'User-Agent' => 'Chrome/43.0.2357.124',
            );
            $config->test['security'] = array(
                'strategy' => "\\App\\Mocks\\Services\\AdsAuthentication",
            );


            $config->init(); // init the config again with new configs

            $app = new Application($config);

            $login = new Login($app);

            $login->default_event($app->request());

            $data = $login->getAll();

            $comparisonKeys = array_keys($loginComparison);
            $dataKeys = array_keys($data['data']);

            foreach ($comparisonKeys as $key) {
                $this->assertTrue(in_array($key, $dataKeys));
            }
        } catch (UnauthorizedException $exception) {
            $this->fail($exception->getMessage());
        } catch (\Exception $exception) {
            $this->fail($exception->getMessage());
        }
    }

    public function testAdsLoginFail()
    {
        try {
            $username = "freetv";
            $password = "WrongPassword";
            $auth = base64_encode("$username:$password");

            $config = new Config();

            $config->test['request']['server'] = array(
                'REQUEST_URI' => '/login/clientId/1/',
                'ENVIRONMENT' => 'test',
            );

            $config->test['request']['headers'] = array(
                'Authorization' => 'Basic ' . $auth,
            );
            $config->test['security'] = array(
                'strategy' => "\\App\\Mocks\\Services\\AdsAuthentication",
            );

            $config->init(); // init the config again with new configs

            $app = new Application($config);

            $login = new Login($app);

            $login->default_event($app->request());

            $this->fail("Login succeeded with wrong password.");
        } catch (UnauthorizedException $exception) {
            return true; //passed test
        } catch (\Exception $exception) {
            $this->fail($exception->getMessage());
        }

    }

    /**
     * @depends testAdsLoginPass
     */
    public function testLoginAPI($data)
    {

//        var_dump($data);
        $testKeys = array_keys(json_decode(file_get_contents(__DIR__ . '/data/Login/fields.json'), true));

//        var_dump($testKeys);
    }

    public function testLoginCache()
    {
        try {
            $username = "freetv";
            $password = "Password1!";
            $auth = base64_encode("$username:$password");

            $config = new Config();

            $config->test['request']['server'] = array(
                'REQUEST_URI' => '/login/clientId/1/',
                'ENVIRONMENT' => 'test',
            );

            $config->test['request']['headers'] = array(
                'Authorization' => 'Basic ' . $auth,
                'User-Agent' => 'Chrome/43.0.2357.124',
            );
            $config->test['security'] = array(
                'strategy' => "\\App\\Mocks\\Services\\AdsAuthentication",
                'AdsConnected' => false
            );
            $config->test['components']['Ads']['domain_controllers'][0] = '192.168.255.255';

            $config->init(); // init the config again with new configs

            $app = new Application($config);


            $login = new Login($app);

            $login->default_event($app->request());

            return true;

        } catch (UnauthorizedException $e) {
            $this->fail($e->getMessage());
        } catch (\Exception $e) {
            $this->fail($e->getMessage());
        }
    }

    public function testPasswordChange()
    {
        try {
            $username = "freetv";
            $password = "Password1!";
            $auth = base64_encode("$username:$password");

            $config = new Config();

            $config->test['request']['server'] = array(
                'REQUEST_URI' => '/login/clientId/1/',
                'ENVIRONMENT' => 'test',
            );

            $config->test['request']['headers'] = array(
                'Authorization' => 'Basic ' . $auth,
            );
            $config->test['security'] = array(
                'strategy' => "\\App\\Mocks\\Services\\AdsAuthentication",
                'AdsConnected' => false
            );

            $config->init(); // init the config again with new configs

            $app = new Application($config);

            $userMdl = new User($app);

            /* set the password to blank */
            $user = $userMdl->findOneByUserId($username);
            $oldpw = $user->getUserPassword();
            $user->setUserPassword("");
            $user->save();

            /* retrieve the empty password (hashed) */
            $user = $userMdl->findOneByUserId($username);
            $passcheck = $user->getUserPassword();

            /* now re-authenticate */
            $app = new Application($config);
            $userMdl = new User($app);
            $user = $userMdl->findOneByUserId($username);
            $password = $user->getUserPassword();

            /* the retrieved password should be the same as it was originally */
            $this->assertNotEquals($passcheck, $password);
            $this->assertEquals($oldpw, $password);

            return true;

        } catch (UnauthorizedException $e) {
            $this->fail($e->getMessage());
        } catch (\Exception $e) {
            $this->fail($e->getMessage());
        }

    }

    /*
     * Delete freetv user, check it's deleted. Then login
     * with the freetv user and see if it's inserted into the db.
     *
     */
    public function testNewUserInsert()
    {
        try {
            $username = "freetv";
            $password = "Password1!";
            $auth = base64_encode("$username:$password");

            $config = new Config();

            $config->test['request']['server'] = array(
                'REQUEST_URI' => '/login/clientId/1/',
                'ENVIRONMENT' => 'test',
            );

            $config->test['request']['headers'] = array(
                'Authorization' => 'Basic ' . $auth,
            );
            $config->test['security'] = array(
                'strategy' => "\\App\\Mocks\\Services\\AdsAuthentication",
                'AdsConnected' => false
            );

            $config->init(); // init the config again with new configs

            $app = new Application($config);

            $userMdl = new User($app);
            $user = $userMdl->findOneByUserId($username);
            $user->deleteById($user->getUserSysid());     //delete the user

            try {
                $userMdl->findOneByUserId($username);
                $this->fail("User didn't get deleted.");
            } catch (NotFoundException $e) {        //we want to get here because the user should be deleted

            }
            /* now re-authenticate */
            $app = new Application($config);
            $userMdl = new User($app);

            $user = $userMdl->findOneByUserId($username);

            return true;

        } catch (UnauthorizedException $e) {
            $this->fail($e->getMessage());
        } catch (NotFoundException $e) {
            $this->fail($e->getMessage());
        } catch (\Exception $e) {
            $this->fail($e->getMessage());
        }
    }
}
