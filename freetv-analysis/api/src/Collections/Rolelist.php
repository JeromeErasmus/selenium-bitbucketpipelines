<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Collections;
use \Elf\Db\AbstractCollection;

/**
 * Description of Rolelist
 *
 * @author michael
 */
class Rolelist extends AbstractCollection 
{
    
//put your code here
    public function getAllRoles()     //don't think this is needed
    {
        $sql = "SELECT role_id as roleId, role_name as roleName, role_slug as roleSlug, role_permission_set rolePermissionSet FROM dbo.roles";   
        
        $data = $this->fetchAllAssoc($sql);
        
        foreach($data as &$row) {
           $row['rolePermissionSet'] = json_decode($row['rolePermissionSet'], true);  
        }
        
        return $data;
    }

    public function fetch() {
        
    }

    public function setParams($params = array()) {
        
    }

}
