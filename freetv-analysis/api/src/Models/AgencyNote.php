<?php
namespace App\Models;

use Elf\Db\AbstractAction;
use Elf\Exception\NotFoundException;
use Elf\Exception\ConflictException;
use Elf\Http\Request;
use App\Utility\Helpers;

class AgencyNote extends AbstractAction
{
    private $noteId;
    private $agencyId;

    protected $fieldMap = array(
        'noteId' => array('name' => 'notes_id')
    );
    
    public function save()
    {
        return $this->createRecord();
    }

        public function getAsArray()
    {
        $ret = array();
        foreach ($this->fieldMap as $key => $val) {
            $ret[$key] = $this->$key;
        }
        return Helpers::convertToBool($ret);
    }
    
    public function createRecord(){
        $sql = "INSERT INTO agency_notes
                  (
                    agency_id,
                    notes_id
                  )
                VALUES
                  (
                    :agency_id,
                    :notes_id
                  )
               ";

        try {
            $id = $this->insert($sql, array(
                ':agency_id' => $this->agencyId,
                ':notes_id' => $this->noteId,
            ));
        } catch(\Exception $e) {
            die($e->getMessage());
        }

        return $id;
    }

    public function setAssocIds($newNoteId, $agencyId)
    {
        $this->agencyId = $agencyId;
        $this->noteId = $newNoteId;
    }

    public function load(){
        
    }
    
}