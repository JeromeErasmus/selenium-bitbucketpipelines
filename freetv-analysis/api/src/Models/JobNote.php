<?php
/**
 * Created by PhpStorm.
 * User: thimira.gunasekera
 * Date: 28/09/2015
 * Time: 4:20 PM
 */

namespace App\Models;

use Elf\Db\AbstractAction;
use Elf\Exception\ConflictException;
use Elf\Exception\NotFoundException;
use Elf\Utility\Convert;

class JobNote extends AbstractAction
{
    protected $jobNoteId;
    protected $jobId;
    protected $userSysId;
    protected $createdAt;
    protected $updatedAt;
    protected $jobNote;
    protected $deleted = false;

    protected $searchDeleted = false;

    protected $fieldMap = array(
        'jobNoteId' => array (
            'name' => 'job_notes_id',
            'type' => 'numeric',
            'required' => false,
            'allowEmpty' => true,
            'expose' => false

        ),
        'jobId' => array (
            'name' => 'job_id',
            'type' => 'numeric',
            'required' => true,
            'allowEmpty' => false,
            'expose' => true

        ),
        'userSysId' => array (
            'name' => 'user_sysid',
            'type' => 'numeric',
            'required' => true,
            'allowEmpty' => false,
            'expose' => true

        ),
        'createdAt' => array (
            'name' => 'created_at',
            'type' => 'string',
            'required' => false,
            'allowEmpty' => true,
            'expose' => true

        ),
        'updatedAt' => array (
            'name' => 'updated_at',
            'type' => 'string',
            'required' => false,
            'allowEmpty' => true,
            'expose' => true

        ),
        'jobNote' => array (
            'name' => 'job_note',
            'type' => 'string',
            'required' => false,
            'allowEmpty' => true,
            'expose' => true

        ),
        'deleted' => array (
            'name' => 'deleted',
            'type' => 'boolean',
            'required' => false,
            'allowEmpty' => true,
            'expose' => false
        )
    );


    public function load()
    {
        $sql = "SELECT job_id, user_sysid, created_at, updated_at, job_note FROM dbo.job_notes WHERE {$this->getSqlDeleted()}  AND job_notes_id = :id";

        $data = $this->fetchOneAssoc($sql,[':id' => $this->getJobNoteId()]);

        if ($data == false) {
            throw new NotFoundException(['displayMessage' => "Cannot find job note with ID {$this->getJobNoteId()}"]);
        }

        $this->setFromArray($data);

        return true;
    }

    public function save()
    {
        $jobNoteId = $this->getJobNoteId();
        
        if( empty($jobNoteId) ) {
            return $this->createRecord();
        } else {
            return $this->updateRecord();
        }
    }

    public function createRecord()
    {
        $sql = "SELECT COUNT(user_sysid) FROM users WHERE user_sysid = :id";
        $count = $this->fetchOneAssoc($sql, [':id' => $this->getUserSysId()]);

        if ($count == 0) {
            throw new NotFoundException("User with id {$this->getUserSysId()} doesn't exist in the system.");
        }
        $sql = "SELECT COUNT(job_id) FROM dbo.jobs WHERE job_id = :id";
        $count = $this->fetchOneAssoc($sql, [':id' => $this->getJobId()]);

        if ($count == 0) {
            throw new NotFoundException("Job with id {$this->getUserSysId()} doesn't exist in the system.");
        }

        $sql = "INSERT INTO dbo.job_notes(job_id, user_sysid, created_at, updated_at, job_note)
                VALUES(:job_id, :user_sysid, getdate(), getdate(), :job_note)";

        try {
            $id = $this->insert($sql, array(
                ':job_id' => $this->getJobId(),
                ':user_sysid' => $this->getUserSysId(),
                ':job_note' => $this->getJobNote(),
            ));
        } catch(\Exception $e) {
            die($e->getMessage());
        }

        $this->setJobNoteId($id);

        return $id;
    }

    public function updateRecord()
    {
        $jobNoteId = $this->getJobId();
        
        if ( empty($jobNoteId) ) {
            throw new NotFoundException("Cannot update as ID is not set");
        }

        $sql = "UPDATE dbo.job_notes
          SET
          job_id = :job_id,
          user_sysid = :user_sysid,
          job_note = :job_note,
          updated_at = getdate(),
          deleted = :deleted
          WHERE job_notes_id = :job_notes_id";

        $this->update($sql, [
            ':job_id' => $this->getJobId(),
            ':user_sysid' => $this->getUserSysId(),
            ':job_note' => $this->getJobNote(),
            ':job_notes_id' => $this->getJobNoteId(),
            ':deleted' => $this->deleted
        ]);
    }

    public function deleteRecord()
    {
        $jobNoteId = $this->getJobNoteId();
        
        if(empty($jobNoteId)) {
            throw new NotFoundException("No Job Note Id set");
        }

        $this->load();      //this should throw an exception if the job note id doesn't exist

        $sql = "UPDATE dbo.job_notes SET deleted = 1 WHERE job_notes_id = :job_notes_id";
        $this->update($sql, [':job_notes_id' => $this->getJobNoteId()]);

    }

    public function getFullJobNote()
    {
        $data = $this->getAsArray();

        $nestedModels = [
            'userSysId' => [
                'model' => 'User',
                'nestedKey' => 'user'
            ]
        ];

        foreach($nestedModels as $property => $details) {
            if(!empty($data[$property])) {

                $model = $this->app->model($details['model']);
                $modelData = $model->getById($data[$property]);

                $data[$details['nestedKey']] = $modelData;
                unset($data[$property]);
            }
        }

        return $data;
    }


    public function getAsArray()
    {
        $data = array();

        foreach($this->fieldMap as $property => $details) {
            if (property_exists($this, $property) && $details['expose'] != false) {
                $data[$property] = $this->$property;
            }
        }
        return $data;
    }

    public function setFromArray($data)
    {
        foreach($this->fieldMap as $key => $mapping) { //loop through the field map and only pick up data that we want
            $setMethod = "set".$key;
            if(method_exists($this, $setMethod) && isset($data[$mapping['name']])) { // check if we can actually update this field
                $this->$setMethod($data[$mapping['name']]);
            } else if(method_exists($this, $setMethod) && isset($data[$key])) { // check if we can actually update this field
                $this->$setMethod($data[$key]);
            }
        }
    }

    /**
     * @return boolean
     */
    public function getSqlDeleted()
    {
        return ($this->searchDeleted === false) ? 'DELETED = 0' : 'DELETED = 1';
    }

    public function setSearchDeleted($deleted)
    {
        $this->searchDeleted = Convert::toBoolean($deleted);
    }

    /**
     * @param boolean $isDeleted
     */
    public function setDeleted($isDeleted)
    {
        $this->deleted = Convert::toBoolean($isDeleted);
    }


    /**
     * @return mixed
     */
    public function getJobNoteId()
    {
        return $this->jobNoteId;
    }

    /**
     * @param mixed $jobNoteId
     */
    public function setJobNoteId($jobNoteId)
    {
        $this->jobNoteId = $jobNoteId;
    }

    /**
     * @return mixed
     */
    public function getJobId()
    {
        return $this->jobId;
    }

    /**
     * @param mixed $jobId
     */
    public function setJobId($jobId)
    {
        $this->jobId = $jobId;
    }

    /**
     * @return mixed
     */
    public function getUserSysId()
    {
        return $this->userSysId;
    }

    /**
     * @param mixed $userSysId
     */
    public function setUserSysId($userSysId)
    {
        $this->userSysId = $userSysId;
    }

    /**
     * @return mixed
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param mixed $createdAt
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
    }

    /**
     * @return mixed
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * @param mixed $updatedAt
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;
    }

    /**
     * @return mixed
     */
    public function getJobNote()
    {
        return $this->jobNote;
    }

    /**
     * @param mixed $jobNote
     */
    public function setJobNote($jobNote)
    {
        $this->jobNote = $jobNote;
    }



}