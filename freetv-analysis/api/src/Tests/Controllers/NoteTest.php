<?php
/**
 * Created by PhpStorm.
 * User: jeremy.paul
 * Date: 8/09/2015
 * Time: 4:44 PM
 */

ini_set('display_errors',1);
error_reporting(E_ALL);

require_once __DIR__.'/../../../vendor/autoload.php';

use App\Config\Config;
use Elf\Application\Application;
use Elf\Exception\UnauthorizedException;
use App\Controllers\Note;
use App\Controllers\Agency;
use App\Controllers\Advertiser;


class NoteTest extends PHPUnit_Framework_TestCase {

    public function testAgencyNoteCollection()
    {
        $agencyNoteCollection= json_decode(file_get_contents(__DIR__.'/data/sampleAgencyNoteCollection.json'), true);
        $getParameters = '';
        foreach($agencyNoteCollection as $key => $value) {
            $getParameters .= '/' . $key . '/' . str_replace(' ', '+',$value);
        }

        try {
            $config = new Config();
            $config->test['request']['server'] = array(
                'REQUEST_URI' => '/Note' . $getParameters,
                'ENVIRONMENT' => 'test',
            );

            $config->test['request']['headers'] = array(
                'User-Agent' => 'Chrome/43.0.2357.124',
            );
            $config->init(); // init the config again with new configs
            $app = new Application($config);
            $agencyController = new Agency($app);
            $data = $agencyController->handleGet($app->request());

            $this->assertTrue($data['status_code'] === 200);

            foreach($data as $note) {
                if(isset($note['note'])){
                    $this->assertTrue(true);
                } else {
                    $this->assertTrue(false);
                }
            }

        }
        catch(UnauthorizedException $exception)
        {
            $this->fail($exception->getMessage());
        }
        catch(\Exception $exception)
        {
            $this->fail($exception->getMessage());
        }
    }

    public function testAgencyNotePost()
    {

        $agencyNoteCollection= json_decode(file_get_contents(__DIR__.'/data/sampleAgencyNoteCollection.json'), true);
        $getParameters = '';
        foreach($agencyNoteCollection as $key => $value) {
            $getParameters .= '/' . $key . '/' . str_replace(' ', '+',$value);
        }

        $agencyNotePostData = json_decode(file_get_contents(__DIR__.'/data/sampleAgencyNotePostData.json'),true);
        $agencyNotePostData = json_encode($agencyNotePostData);

        try {
            $config = new Config();
            $config->test['request']['server'] = array(
                'REQUEST_URI' => '/Note' . $getParameters,
                'REQUEST_METHOD' => 'POST',
                'rawPostInput' => $agencyNotePostData,
                'ENVIRONMENT' => 'test',
            );

            $config->test['request']['headers'] = array(
                'User-Agent' => 'Chrome/43.0.2357.124',
                'Content-Type' => 'application/json',
            );

            $config->init(); // init the config again with new configs
            $app = new Application($config);

            $agencyNoteController = new Note($app);

            $agencyNoteController->handlePost($app->request());

            $data = $agencyNoteController->getAll();

            $this->assertTrue($data['status_code'] === 201);
            $location = explode('/', $data['locationUrl']);
            $agencyNoteId = end($location);

            if(!empty($agencyNoteId)){
                $this->assertTrue(true);
            } else {
                $this->assertTrue(false);
            }

        }
        catch(UnauthorizedException $exception)
        {
            $this->fail($exception->getMessage());
        }
        catch(\Exception $exception)
        {
            $this->fail($exception->getMessage());
        }

    }
    
    #-------------------------------------------------------------------------------------------------------------------
    public function testAdvertiserNoteCollection()
    {
        $advertiserNoteCollection= json_decode(file_get_contents(__DIR__.'/data/sampleAdvertiserNoteCollection.json'), true);
        $getParameters = '';
        foreach($advertiserNoteCollection as $key => $value) {
            $getParameters .= '/' . $key . '/' . str_replace(' ', '+',$value);
        }

        try {
            $config = new Config();
            $config->test['request']['server'] = array(
                'REQUEST_URI' => '/Note' . $getParameters,
                'ENVIRONMENT' => 'test',
            );

            $config->test['request']['headers'] = array(
                'User-Agent' => 'Chrome/43.0.2357.124',
            );
            $config->init(); // init the config again with new configs
            $app = new Application($config);
            $advertiserController = new Advertiser($app);
            $data = $advertiserController->handleGet($app->request());

            $this->assertTrue($data['status_code'] === 200);

            foreach($data as $note) {
                if(isset($note['note'])){
                    $this->assertTrue(true);
                } else {
                    $this->assertTrue(false);
                }
            }

        }
        catch(UnauthorizedException $exception)
        {
            $this->fail($exception->getMessage());
        }
        catch(\Exception $exception)
        {
            $this->fail($exception->getMessage());
        }
    }

    public function testAdvertiserNotePost()
    {

        $advertiserNoteCollection= json_decode(file_get_contents(__DIR__.'/data/sampleAdvertiserNoteCollection.json'), true);
        $getParameters = '';
        foreach($advertiserNoteCollection as $key => $value) {
            $getParameters .= '/' . $key . '/' . str_replace(' ', '+',$value);
        }

        $advertiserNotePostData = json_decode(file_get_contents(__DIR__.'/data/sampleAdvertiserNotePostData.json'),true);
        $advertiserNotePostData = json_encode($advertiserNotePostData);

        try {
            $config = new Config();
            $config->test['request']['server'] = array(
                'REQUEST_URI' => '/Note' . $getParameters,
                'REQUEST_METHOD' => 'POST',
                'rawPostInput' => $advertiserNotePostData,
                'ENVIRONMENT' => 'test',
            );

            $config->test['request']['headers'] = array(
                'User-Agent' => 'Chrome/43.0.2357.124',
                'Content-Type' => 'application/json',
            );

            $config->init(); // init the config again with new configs
            $app = new Application($config);

            $advertiserNoteController = new Note($app);

            $advertiserNoteController->handlePost($app->request());

            $data = $advertiserNoteController->getAll();

            $this->assertTrue($data['status_code'] === 201);
            $location = explode('/', $data['locationUrl']);
            $advertiserNoteId = end($location);

            if(!empty($advertiserNoteId)){
                $this->assertTrue(true);
            } else {
                $this->assertTrue(false);
            }

        }
        catch(UnauthorizedException $exception)
        {
            $this->fail($exception->getMessage());
        }
        catch(\Exception $exception)
        {
            $this->fail($exception->getMessage());
        }

    }
    
    

    public function testDelete()
    {

    }

}