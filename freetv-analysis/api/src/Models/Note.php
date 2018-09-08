<?php
namespace App\Models;

use Elf\Db\AbstractAction;
use Elf\Exception\NotFoundException;
use Elf\Exception\ConflictException;
use App\Utility\Helpers;


class Note extends AbstractAction
{
    private $noteId;
    private $note;
    private $userId;
    private $deletedByUserId;
    private $noteTypeId;
    private $createdAt;
    private $updatedAt;
    private $accountNoteType = 3;
    private $cadNoteType = 2;

    protected $fieldMap = array(
        'noteId' => array(
            'name' => 'id',
            'type' => 'numeric',
            'required' => false,
            'allowEmpty' => false
        ),
        'note' => array(
            'name' => 'note',
            'type' => 'string',
            'required' => true,
            'allowEmpty' => false
        ),
        'userId' => array(
            'name' => 'user_sysid',
            'type' => 'numeric',
            'required' => true,
            'allowEmpty' => false
        ),
        'noteTypeId' => array(
            'name' => 'note_type_id',
            'type' => 'numeric',
            'required' => false,
            'allowEmpty' => true
        ),
        'createdAt' => array(
            'name' => 'created_at',
            'type' => 'string',
            'required' => false,
            'allowEmpty' => true
        ),
        'updatedAt' => array(
            'name' => 'updated_at',
            'type' => 'string',
            'required' => false,
            'allowEmpty' => true
        ),

    );


    public function save()
    {
        $noteId = $this->getNoteId();
        if (!empty($noteId)) {
            return $this->updateRecord();
        } else {
            return $this->createRecord();
        }
    }

    public function createRecord(){
        //check the user type accessing the save function
        // im removing this for now since we don't konw about permissions e.g. with this an admin can't submit
        // different types of notes with this code
        $sql = "
                SELECT
                  r.role_name
                FROM roles r
                JOIN users u
                  ON u.user_role_id = r.role_id
                WHERE u.user_id = :user_id
                ";
        $params = array(':user_id' => $this->userId);
        $data = $this->fetchOneAssoc($sql, $params);
        //set the userTypeId

        if($data['role_name'] == 'Accounts'){
            $this->noteTypeId = $this->accountNoteType;
        }
        elseif($data['role_name'] == 'CAD'){
            $this->noteTypeId = $this->cadNoteType;
        }

        if (empty($this->noteTypeId)) {
            $this->noteTypeId = $this->cadNoteType; //fallback for legacy stuff
        }

        $sql = "
                INSERT into
                  notes(
                    note_type_id,
                    user_sysid,
                    created_at,
                    note,
                    deleted
                  )
                VALUES
                  (
                    :note_type_id,
                    :user_sys_id,
                    GETDATE(),
                    :note,
                    0
                  )
                ";

        $id = $this->insert($sql,
            array(
                ':note_type_id' => $this->noteTypeId,
                ':user_sys_id'  => $this->userId,
                ':note'         => $this->note,
            )
        );

        $this->noteId = $id;
        return $id;
    }

    public function updateRecord()
    {
        if(empty($this->noteId)) {
            throw new NotFoundException("note id not set");
        }

        $sql = "UPDATE notes SET
        note = :note,
        note_type_id = :note_type_id,
        updated_at = GETDATE()
        WHERE notes_id = :note_id
        ";

        $this->update($sql, [
            ':note_id' => $this->getNoteId(),
            ':note' => $this->getNote(),
            ':note_type_id' => $this->getNoteTypeId()
        ]);

        return true;

    }

    public function getFullNote()
    {
        $data = $this->getAsArray();
        if (isset($data['userId'])) {
            $data['user'] = $this->app->model('User')->findOneByUserSysId($data['userId'])->getAsArray();
            unset($data['user']['userPermissionSet']);
            unset($data['userId']);
        }

        return $data;
    }

    /**
     * get the data as an array
     * @return type
     */
    public function getAsArray() {
        $returnArray = array();
        foreach ($this->fieldMap as $key => $mapping) { //loop through the field map and only pick up data that we want

            $getMethod = "get" . ucfirst($key);

            if (method_exists($this, $getMethod) &&
                (isset($mapping['expose']) &&
                    false !== $mapping['expose']) || !isset($mapping['expose'])
            ) { // check if we can actually update this field
                $returnArray[$key] = $this->$getMethod();
            }
        }

        return $returnArray;
    }



    /**
     * set all the properties with an array of data
     * @param $data
     */
    public function setFromArray($data) {
        foreach ($this->fieldMap as $key => $mapping) { //loop through the field map and only pick up data that we want
            $setMethod = "set" . $key;
            if (method_exists($this, $setMethod) && isset($data[$mapping['name']])) { // check if we can actually update this field
                $this->$setMethod($data[$mapping['name']]);
            } else if (method_exists($this, $setMethod) && isset($data[$key])) { // check if we can actually update this field
                $this->$setMethod($data[$key]);
            }
        }
    }

    public function deleteRecord()
    {
        $sql = "
                UPDATE notes
                  SET 
                    deleted = 1,
                    deleted_by = :user_id,
                    deleted_at = GETDATE()
                  WHERE notes_id = :note_id
                ";

        try {
            $id = $this->update($sql,
                array(
                    ':note_id' => $this->noteId,
                    ':user_id' => $this->deletedByUserId,
                )
            );
        } catch(\Exception $e) {
            throw new \Exception("could not delete entity with id: " . $this->noteId);
        }

    }

    public function load()
    {
        if (!$this->getNoteId()) {
            throw new \Exception("note id not set");
        }

        $params = array(
            ':note_id' => $this->getNoteId(),
        );

        $sql = "SELECT * FROM notes where notes.notes_id = :note_id";

        $data = $this->fetchOneAssoc($sql, $params);

        if(!empty($data))
        {
            $this->setFromArray($data);
            return;
        }
        throw new NotFoundException("entity not found");
    }

    public function setNoteId($id){
        $this->noteId = $id;
    }

    public function setDeletedById($id){
        $this->deletedByUserId = $id;
    }

    /**
     * @return mixed
     */
    public function getNoteId()
    {
        return $this->noteId;
    }


    /**
     * @return mixed
     */
    public function getNote()
    {
        return $this->note;
    }

    /**
     * @param mixed $note
     */
    public function setNote($note)
    {
        $this->note = $note;
    }

    /**
     * @return mixed
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param mixed $userId
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;
    }

    /**
     * @return mixed
     */
    public function getNoteTypeId()
    {
        return $this->noteTypeId;
    }

    /**
     * @param mixed $noteTypeId
     */
    public function setNoteTypeId($noteTypeId)
    {
        $this->noteTypeId = $noteTypeId;
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





}