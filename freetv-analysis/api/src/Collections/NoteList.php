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

class NoteList extends AbstractCollection
{
    public $list;
    private $sqlParams;
    private $filter;
    private $noteId;

    protected $fieldMap = array (
        'noteId' => array(
            'name' => 'notes_id',
            'type' => 'string',
            'required' => true,
            'allowEmpty' => false
        ),
        'noteTypeId' => array(
            'name' => 'notes_type_id',
            'type' => 'string',
            'required' => true,
            'allowEmpty' => false
        ),'userSysId' => array(
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
        'note' => array(
            'name' => 'notes',
            'type' => 'string',
            'required' => true,
            'allowEmpty' => false
        ),

    );

    public function setParams($params = array())
    {
        if ( array_key_exists('noteId',$params) ) {

            if (!is_numeric($params['noteId'])) {
                throw new MalformedException("noteId not a number");
            }
            $this->noteId = $params['noteId'];
            $this->filter = "AND notes_id = :noteId";
            $this->sqlParams = [':noteId' => $params['noteId']];
        }
    }

    public function fetch()
    {
        $sql = "SELECT notes_id, note_type_id, user_sysid, created_at, updated_at, note FROM dbo.notes
                WHERE deleted = 0 {$this->filter}
                ORDER BY created_at DESC";
        $data = $this->fetchAllAssoc($sql, $this->sqlParams);
        $jobNotes = array();

        if ($data === false) {
            $this->list = false;
            throw new NotFoundException([ 'displayMessage' => "No notes found"]);
        }

        // sorry for the nested statements, can't make setters/getters as it's all in one list variable
        foreach($data as $id => $note) {
            $notes[$id] = array();
            $keys = array_keys($note);
            foreach($this->fieldMap as $fieldName => $details) {
                if (in_array($details['name'], $keys)) {
                    if (isset($details['type']) && $details['type'] == 'boolean') {
                        $notes[$id][$fieldName] = Convert::toBoolean($note[$details['name']]);
                    } else {
                        $notes[$id][$fieldName] = $note[$details['name']];
                    }
                }
            }
        }
        
        
        $this->list = $notes;

    
    }

    /**
     * Retrieves nested user model in the notes
     */
    public function getFullNotes()
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