<?php
/**
 * Created by PhpStorm.
 * User: thimira.gunasekera
 * Date: 30/09/2015
 * Time: 11:46 AM
 */

namespace App\Tests\Models;

require_once __DIR__.'/../../../vendor/autoload.php';

use App\Collections\JobNoteList;
use App\Config\Config;
use App\Models\JobNote;
use Elf\Application\Application;
use Elf\Exception\NotFoundException;

class JobNoteTest extends \PHPUnit_Framework_TestCase
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

    public function testGetFilteredCollection()
    {
        $requiredKeys = json_decode(file_get_contents(__DIR__.'/data/jobnotelist.keys.json'), true);
        $collection = new JobNoteList($this->app);

        $collection->fetch();

        $data = $collection->getFullJobNotes();

        // check entire collection
        foreach ($requiredKeys as $key) {
            $this->assertArrayHasKey($key, $data[rand(0,count($data)-1)] );
        }

        $randomJobId = $data[rand(0,count($data)-1)]['jobId'];

        //now check filtered collection
        $collection = new JobNoteList($this->app);

        $collection->setParams(['jobId' => $randomJobId ]);
        $collection->fetch();
        $data = $collection->getFullJobNotes();

        foreach( $data as $jobNote ) {
            $this->assertEquals($randomJobId, $jobNote['jobId']);       //make sure the filtered job Id's are only from the one in the param
        }

        return $data[rand(0,count($data)-1)]['jobId'];       //return a random job note id for get single test
    }

    /**
     * @param $id
     * @depends testGetFilteredCollection
     */
    public function testCreateUpdateDelete($id)
    {
        /* create a new advertiser */
        $jobnote = new JobNote($this->app);

        $originalData = json_decode(file_get_contents(__DIR__.'/data/jobnote.post.json'), true);

        $jobnote->setFromArray($originalData);
        $id = $jobnote->save();

        if (empty($id)) {
            $this->fail("Failed to save a new job note.");
        }

        /* get the created advertiser and check all fields saved */
        $jobnote = new JobNote($this->app);
        try {
            $jobnote->setJobNoteId($id);
            $jobnote->load();
        } catch (NotFoundException $e) {
            $this->fail($e->getMessage());
        }

        $retrievedData = $jobnote->getFullJobNote();

        $this->assertEquals($originalData['jobId'], $retrievedData['jobId']);
        $this->assertEquals($originalData['jobNote'], $retrievedData['jobNote']);

        /* now update the fields */
        $updateData = json_decode(file_get_contents(__DIR__.'/data/jobnote.patch.json'), true);

        $jobnote->setFromArray($updateData);
        $jobnote->save();

        /* now check if the fields got updated */
        $jobnote = new JobNote($this->app);

        try {
            $jobnote->setJobNoteId($id);
            $jobnote->load();
        } catch (NotFoundException $e) {
            $this->fail($e->getMessage());
        }

        $retrievedUpdate = $jobnote->getFullJobNote();

        $this->assertEquals($updateData['jobNote'], $retrievedUpdate['jobNote']);
        $this->assertNotEquals($originalData['jobNote'], $retrievedUpdate['jobNote']);

        /* now delete the entry */
        try {
            $jobnote->deleteRecord();
        } catch (\Exception $e) {
            $this->fail($e->getMessage());
        }

        /* now verify it was deleted */
        $jobnote = new JobNote($this->app);
        try {
            $jobnote->setJobNoteId($id);
            $jobnote->load();
            $this->fail("Job note did not get deleted");
        } catch(NotFoundException $e) {

        } catch (\Exception $e) {
            $this->fail($e->getMessage());
        }
    }
}