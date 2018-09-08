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
class Userlist extends AbstractCollection 
{

    //put your code here
    public function getAllUsers()     //dont think this is needed
    {
        $sql = "SELECT "
                . "user_sysid as userSysid, "
                . "user_id as userId, "
                . "user_email as userEmail, "
                . "user_email as userEmail,  "
                . "user_name as userName, "
                . "user_first_name as userFirstName, "
                . "user_last_name as userLastName,"
                . "user_active as userActive, "
                . "user_role_id as userRoleId "
                . "FROM dbo.users "
                . "WHERE deleted <> 1";

        $data = $this->fetchAllAssoc($sql);        
       
        return $data;
    }

    public function fetch() {
        
    }

    public function setParams($params = array()) {
        
    }

}
