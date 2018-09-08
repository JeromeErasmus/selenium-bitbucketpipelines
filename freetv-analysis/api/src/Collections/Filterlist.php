<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Collections;

class Filterlist extends \Elf\Db\AbstractCollection {

        
    public function getUserFilters($userIds)
    {
        $where = "";
        if (strpos($userIds, '|') != 0) {       // multiple users
            $users = explode('|', $userIds);
            foreach ($users as $key=>$value) {
                $where .= "OR user_id = :userId".$key." ";
                $params[":userId".$key] = $value;
            }
            $where = substr_replace($where, "", 0, 2);       //remove the first OR
        } else {
            $where = "user_id = :userId";
            $params[':userId'] = $userIds;
        }
        if(!empty($where)){
            $where = "( user_to_filter.user_id IS NULL OR ".$where.")";
            $where .= " AND filters.saved = 1 ";
        }else{
            $where  = " filters.saved = 1 " ;
        }

        $sql = "SELECT
                filters.filter_id, filters.filter_name, 
                filters.filter_details,filters.created_at, filters.saved, user_to_filter.user_id
                FROM dbo.filters
                LEFT JOIN dbo.user_to_filter on user_to_filter.filter_id = filters.filter_id
                WHERE $where";        
        $data = $this->fetchAllAssoc($sql, $params);

        if ($data === false) {      //no filters for specified id
            return false;
        }
        
        //get the user
        $roleId = $this->app->service('user')->getCurrentUser()->getUserRoleId();
        if($roleId != ADMINSTRATOR){ 
            // if not administrator we need to remove all jobs in progress prebuild filter
            unset($data[ALL_JOBS_IN_PROGRESS]); 
        }

        foreach($data as &$row) {
            $row['filterId'] = $row['filter_id'];
            unset($row['filter_id']);
            $row['filterName'] = $row['filter_name'];
            unset($row['filter_name']);
            $row['createdAt'] = $row['created_at'];
            unset($row['created_at']);
            $row['filterDetails'] = json_decode($row['filter_details'], true);
            unset($row['filter_details']);
        }
        return $data;
    }
    
    public function getAllFilters()     //dont think this is needed
    {
        $sql = "SELECT 
            filter_name, filter_details, created_at
            FROM
            dbo.filters";   
        
        $data = $this->fetchAllAssoc($sql);
        
        foreach($data as &$row) {
           $row['filter_details'] = json_decode($row['filter_details'], true);  
        }
        
        return $data;
    }
    public function fetch(){}
    
    public function setParams($params = array()){}
    
}
