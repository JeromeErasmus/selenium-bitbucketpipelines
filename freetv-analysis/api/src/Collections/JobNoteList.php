<?php
/**
 * Created by PhpStorm.
 * User: thimira.gunasekera
 * Date: 29/09/2015
 * Time: 4:26 PM
 */

namespace App\Collections;


use Elf\Db\AbstractCollection;
use Elf\Exception\MalformedException;
use Elf\Exception\NotFoundException;

class JobNoteList extends AbstractCollection
{
    public $list;
    private $sqlParams;
    private $filter;
    private $jobId;

    protected $fieldMap = array (
        'jobNoteId' => array(
            'name' => 'job_notes_id',
            'type' => 'string',
            'required' => true,
            'allowEmpty' => false
        ),
        'jobId' => array(
            'name' => 'job_id',
            'type' => 'string',
            'required' => true,
            'allowEmpty' => false
        ),
        'userSysId' => array(
            'name' => 'user_sysid',
            'type' => 'string',
            'required' => true,
            'allowEmpty' => false
        ),
        'createdAt' => array(
            'name' => 'created_at',
            'type' => 'string',
            'required' => true,
            'allowEmpty' => false
        ),
        'updatedAt' => array(
            'name' => 'updated_At',
            'type' => 'string',
            'required' => true,
            'allowEmpty' => false
        ),
        'jobNote' => array(
            'name' => 'job_note',
            'type' => 'string',
            'required' => true,
            'allowEmpty' => false
        ),

    );

    public function setParams($params = array())
    {
        if ( array_key_exists('jobId',$params) ) {

            if (!is_numeric($params['jobId'])) {
                throw new MalformedException("jobId not a number");
            }
            $this->jobId = $params['jobId'];
            $this->filter = "AND job_id = :jobId";
            $this->sqlParams = [':jobId' => $params['jobId']];
        }
    }

    public function fetch()
    {
        $sql = "SELECT job_notes_id, job_id, user_sysid, created_at, updated_at, job_note FROM dbo.job_notes
                WHERE deleted = 0 {$this->filter}
                ORDER BY created_at DESC";
        $data = $this->fetchAllAssoc($sql, $this->sqlParams);
        $jobNotes = array();

        if ($data === false) {
            $this->list = false;
            throw new NotFoundException([ 'displayMessage' => "No job notes found for given job id {$this->jobId}"]);
        }

        // sorry for the nested statements, can't make setters/getters as it's all in one list variable
        foreach($data as $id => $jobNote) {
            $jobNotes[$id] = array();
            $keys = array_keys($jobNote);
            foreach($this->fieldMap as $fieldName => $details) {
                if (in_array($details['name'], $keys)) {
                    if (isset($details['type']) && $details['type'] == 'boolean') {
                        $jobNotes[$id][$fieldName] = Convert::toBoolean($jobNote[$details['name']]);
                    } else {
                        $jobNotes[$id][$fieldName] = $jobNote[$details['name']];
                    }
                }
            }
        }
        
        
        $this->list = $jobNotes;

    
    }

    /**
     * Retrieves nested user model in the job notes
     */
    public function getFullJobNotes()
    {
        $data = $this->list;

        $nestedModels = [
            'userSysId' => [
                'model' => 'User',
                'nestedKey' => 'user'
            ]
        ];
        
        

        foreach($data as $id => $individualJob) {

            foreach($nestedModels as $property => $details) {
                if(!empty($data[$id][$property])) {

                    $model = $this->app->model($details['model']);
                    $modelData = $model->getById($data[$id][$property]);
                    $data[$id][$details['nestedKey']] = $modelData;
                    unset($data[$id][$property]);
                }
            }

        }


        return $data;
    }
}