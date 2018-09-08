<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Collections;
use \Elf\Db\AbstractCollection;
use \Elf\Utility\Convert;

class AgencyNotes extends AbstractCollection
{
    private $agencyId;

    //put your code here
    public function getAllAgencyNotes($agencyId = null)
    {
        if(empty($agencyId)){
            throw new \Exception("No Agency ID provided");
        }

        $where = ' AND 1=1 ';

        $data = $this->app->service('User')->getCurrentUser();
        if(!isset($data)){
            $where .= "AND nt.name = 'Account'";
        }

        $sql = "SELECT
                  n.notes_id as id,
                  nt.name,
                  n.note,
                  u.user_name as userName,
                  n.created_at,
                  n.updated_at,
                 CASE
		            WHEN n.updated_at is not null THEN n.updated_at
		          ELSE n.created_at
		          END
		          AS updated_created
                FROM agency_notes agn
                  JOIN notes n
                    ON agn.notes_id = n.notes_id
                  LEFT JOIN note_type nt
                    on nt.id = n.note_type_id
                  JOIN users u
                    on u.user_sysid = n.user_sysid
                WHERE agn.agency_id = :ag_id AND n.deleted != 1 {$where}
                ORDER BY updated_created DESC
                ";

        $params = array(
            ':ag_id' => $agencyId
        );

        $data = $this->fetchAllAssoc($sql, $params);

        if(empty($data)) {
            throw new \Exception("Not Found");
        }

        return $data;
    }
    
    public function fetch() {
        return $this->getAllAgencyNotes($this->agencyId);
    }

    public function setParams($params = array()) {

    }
    
    public function setId($id){
        $this->agencyId = $id;
    }
}
