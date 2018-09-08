<?php
/**
 * Created by PhpStorm.
 * User: thimira.gunasekera
 * Date: 8/01/2016
 * Time: 2:41 PM
 */

namespace App\Tests\Models;

ini_set('display_errors',1);
error_reporting(E_ALL);

require_once __DIR__.'/../../../vendor/autoload.php';

use App\Models\LastSelectedFilter;
use App\Models\User;
use App\Config\Config;
use Elf\Application\Application;



class LastSelectedFilterTest extends \PHPUnit_Framework_TestCase
{
    private $config;
    private $app;
    private $testData;

    public function setUp()
    {
        $this->config = new Config();
        $this->config->test['request']['server'] = array(
            'REQUEST_URI' => '/lastSelectedFilter/clientId/1',
            'REQUEST_METHOD' => 'GET',
            'ENVIRONMENT' => 'test',
        );



        $this->config->test['request']['headers'] = array(
            'User-Agent' => 'Chrome/43.0.2357.124',
            'Content-Type' => 'application/json',
            'Authorization' => 'Basic dGhpbWlyYS5ndW5hc2VrZXJhOmFwcGxlczEyMzQ1Ng=='
        );

        $this->config->init();
        $this->app = new Application($this->config);
    }



    public function testUpdateLastFilter()
    {
        $lastSelectedFilterMdl = new LastSelectedFilter($this->app);
        $sysid = $this->app->service('user')->getCurrentUser()->getUserSysid();
        $this->testData = $lastSelectedFilterMdl->getFilterBySysId($sysid)[0];
        $userId = $this->app->service('user')->getCurrentUser()->getUserId();
        $patchData = json_decode(file_get_contents(__DIR__.'/data/LastSavedFilter/lastselectedfilter.post.json'), true);

        try {
            $lastSelectedFilterMdl->setLastSelectedFilter($userId, $patchData);
        } catch (\Exception $e) {
            $this->fail("Failed to update the last selected filter: ", $e->getMessage());
        }

        $this->assertTrue(true);
        return $this->testData;
    }

    /**
     * @depends testUpdateLastFilter
     */
    public function testGetLastFilter($comparison)
    {
        $sysId = $this->app->service('user')->getCurrentUser()->getUserSysid();
        $userId = $this->app->service('user')->getCurrentUser()->getUserId();
        $lastSelectedFilterMdl = new LastSelectedFilter($this->app);
        $filterData = $lastSelectedFilterMdl->getFilterBySysId($sysId);

        $fieldComparison = json_decode(file_get_contents(__DIR__.'/data/LastSavedFilter/lastselectedfilter.get.json'), true);

        // make sure retrieved data has all keys present
        $retrievedDataKeys = array_keys($filterData[0]['filterDetails']);
        foreach($fieldComparison[0]['filterDetails'] as $key => $val) {
            if (in_array($key, $retrievedDataKeys)) {
                $this->assertTrue(true);
            } else {
                $this->fail("key $key not present in retrieved data");
            }
        }

        // now check if last filter updated fine
        $this->assertEquals($comparison, $filterData[0]);

        try {
            $lastSelectedFilterMdl->setLastSelectedFilter($userId, $comparison);        //now revert to original data
        } catch (\Exception $e) {
            $this->fail("Failed to update the last selected filter: ", $e->getMessage());
        }
    }
}