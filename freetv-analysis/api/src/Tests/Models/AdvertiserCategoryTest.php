<?php
/**
 * Created by PhpStorm.
 * User: thimira.gunasekera
 * Date: 15/09/2015
 * Time: 3:26 PM
 */

namespace App\Tests\Models;

ini_set('display_errors',1);
error_reporting(E_ALL);

require_once __DIR__.'/../../../vendor/autoload.php';

use App\Models\AdvertiserCategory;
use App\Config\Config;
use Elf\Application\Application;
use App\Collections\AdvertiserCategoryList;
use Elf\Exception\NotFoundException;


class AdvertiserCategoryTest extends \PHPUnit_Framework_TestCase
{
    private $config;
    private $app;

    public function setUp()
    {
        $this->config = new Config();
        $this->config->test['request']['server'] = array(
            'REQUEST_URI' => '/AdvertiserCategory/clientId/1',
            'REQUEST_METHOD' => 'GET',
            'ENVIRONMENT' => 'test',
        );

        $this->config->test['request']['headers'] = array(
            'Content-Type' => 'application/json',
        );

        $this->config->init();
        $this->app = new Application($this->config);
    }

    public function testAdvCategoryCollection()
    {
        $requiredKeys = json_decode(file_get_contents(__DIR__.'/data/advertisercategorylist.keys.json'), true);
        $collection = new AdvertiserCategoryList($this->app);

        $collection->fetch();

        foreach ($requiredKeys as $key) {
            $this->assertArrayHasKey($key, $collection->list[rand(0,count($collection->list)-1)] );
        }

        return $collection->list[rand(0,count($collection->list)-1)]['advertiserCategoryId'];       //return a random adv_id for get single test
    }

    /**
     * @param $id
     *
     * @depends testAdvCategoryCollection
     */
    public function testGetSingleAdvCategory($id)
    {
        $advertiserCategory = new AdvertiserCategory($this->app);

        try {
            $advertiserCategory = $advertiserCategory->findCategoryById($id);

        } catch(NotFoundException $e) {
            $this->fail($e->getMessage());
        }

        $result = $advertiserCategory->getAsArray();
        $testKeys = json_decode(file_get_contents(__DIR__.'/data/advertisercategory.keys.json'), true);

        foreach ($testKeys as $key){
            $this->assertArrayHasKey($key, $result);
        }
    }

    public function testCreateUpdateDelete()
    {
        /* create a new advertiser */
        $advertiser = new AdvertiserCategory($this->app);

        $originalData = json_decode(file_get_contents(__DIR__.'/data/advertisercategory.post.json'), true);
        $advCatCode = chr(rand(65,90)).chr(rand(65,90));       //random A-Z{2}
        $originalData['advertiserCode'] = $advCatCode;

        $advertiser->setFromArray($originalData);
        $id = $advertiser->save();

        if (empty($id)) {
            $this->fail("Failed to save a new advertiser category.");
        }

        /* get the created advertiser and check all fields saved */
        $advertiser = new AdvertiserCategory($this->app);
        try {
            $advertiser = $advertiser->findCategoryById($id);
        } catch (NotFoundException $e) {
            $this->fail($e->getMessage());
        }

        $retrievedData = $advertiser->getAsArray();
        $this->assertEquals($id, $retrievedData['categoryId']);
        unset($retrievedData['categoryId']);

        $this->assertEquals($originalData, $retrievedData);

        /* now update the fields */

        $updateData = json_decode(file_get_contents(__DIR__.'/data/advertisercategory.patch.json'), true);

        $advertiser->setFromArray($updateData);
        $advertiser->save();

        /* now check if the fields got updated */
        $advertiser = new AdvertiserCategory($this->app);
        $advertiser = $advertiser->findCategoryById($id);
        $retrievedUpdate = $advertiser->getAsArray();

        foreach ($updateData as $key=>$val){
            $this->assertTrue($val == $retrievedUpdate[$key]);
        }

        /* now delete the entry */
        try {
            $advertiser->deleteById($id);
        } catch (\Exception $e) {
            $this->fail($e->getMessage());
        }

        /* now verify it was deleted */
        $advertiser = new AdvertiserCategory($this->app);
        try {
            $advertiser->findCategoryById($id);
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