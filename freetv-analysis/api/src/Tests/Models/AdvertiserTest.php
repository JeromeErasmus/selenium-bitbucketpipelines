<?php
/**
 * Created by PhpStorm.
 * User: thimira.gunasekera
 * Date: 9/09/2015
 * Time: 9:15 AM
 */

namespace App\Tests\Models;

ini_set('display_errors',1);
error_reporting(E_ALL);

require_once __DIR__.'/../../../vendor/autoload.php';

use App\Collections\Advertiserlist;
use App\Config\Config;
use App\Models\Advertiser;
use Elf\Application\Application;
use Elf\Exception\NotFoundException;
use App\Utility\Helpers;

class AdvertiserTest extends \PHPUnit_Framework_TestCase
{
    private $config;
    private $app;

    public function setUp()
    {
        $this->config = new Config();
        $this->config->test['request']['server'] = array(
            'REQUEST_URI' => '/Advertiser/clientId/1',
            'REQUEST_METHOD' => 'GET',
            'ENVIRONMENT' => 'test',
        );

        $this->config->test['request']['headers'] = array(
            'Content-Type' => 'application/json',
        );

        $this->config->init();
        $this->app = new Application($this->config);
    }

    public function testGetAdvertiserCollection()
    {
        $requiredKeys = json_decode(file_get_contents(__DIR__.'/data/advertiserlist.keys.json'), true);
        $collection = new Advertiserlist($this->app);

        $collection->setParams(array('active' => 'true'));
        $collection->fetch();

        foreach ($requiredKeys as $key) {
            $this->assertArrayHasKey($key, $collection->list[rand(0,count($collection->list)-1)] );
        }

        return $collection->list[rand(0,count($collection->list)-1)]['advertiserId'];       //return a random adv_id for get single test


    }

    /**
     * @depends testGetAdvertiserCollection
     */
    public function testGetSingleAdvertiser($adv_id)
    {
        $advertiser = new Advertiser($this->app);

        try {
            $advertiser = $advertiser->findByAdvertiserId($adv_id);

        } catch(NotFoundException $e) {
            $this->fail($e->getMessage());
        }

        $result = $advertiser->getAsArray();
        $testKeys = json_decode(file_get_contents(__DIR__.'/data/advertiser.keys.json'), true);

        foreach ($testKeys as $key){
            $this->assertArrayHasKey($key, $result);
        }
    }

    public function testCreateUpdateDelete()
    {
        /* create a new advertiser */
        $advertiser = new Advertiser($this->app);

        $originalData = Helpers::convertToBool(json_decode(file_get_contents(__DIR__.'/data/advertiser.post.json'), true));
        $advCode = "AT".rand(1000,9999);
        $originalData['advertiserCode'] = $advCode;
        $advertiser->setFields($originalData);
        $id = $advertiser->save();

        if (empty($id)) {
            $this->fail("Failed to save a new advertiser.");
        }

        /* get the created advertiser and check all fields saved */
        $advertiser = new Advertiser($this->app);
        try {
            $advertiser = $advertiser->findByAdvertiserId($id);
        } catch (NotFoundException $e) {
            $this->fail($e->getMessage());
        }
        
        $retrievedData = Helpers::convertToBool($advertiser->getAsArray());
        $this->assertEquals($id, $retrievedData['advertiserId']);
        unset($retrievedData['advertiserId']);

        $this->assertEquals($originalData, $retrievedData);

        /* now update the fields */
        $updateData = json_decode(file_get_contents(__DIR__.'/data/advertiser.patch.json'), true);
        $advertiser->setFields($updateData);
        $advertiser->save();

        /* now check if the fields got updated */
        $advertiser = new Advertiser($this->app);
        $advertiser = $advertiser->findByAdvertiserId($id);
        $retrievedUpdate = $advertiser->getAsArray();

        foreach ($updateData as $key=>$val){
            
            $this->assertTrue($val == $retrievedUpdate[$key]);
        }

        /* now delete the entry */
        try {
            $advertiser->deleteRecord();
        } catch (\Exception $e) {
            $this->fail($e->getMessage());
        }

        /* now verify it was deleted */
        $advertiser = new Advertiser($this->app);
        try {
            $advertiser->findByAdvertiserId($id);
            $this->fail("Advertise did not get deleted");
        } catch(NotFoundException $e) {

        } catch (\Exception $e) {
            $this->fail($e->getMessage());
        }
    }

    public function tearDown()
    {
        $this->app = null;
        $this->config = null;
    }




}