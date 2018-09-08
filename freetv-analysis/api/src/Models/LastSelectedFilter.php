<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Models;
use Elf\Application\Application;


class LastSelectedFilter extends \Elf\Db\AbstractAction {
    
    
    
    public function getFilterBySysId($sysId){
        
        //get the filterIds
        $filterIds = $this->app->service('user')->getCurrentUser()->getUserJfiId();
        
        $sql = "SELECT 
                    filter_id as filterId, filter_details
                FROM 
                    filters 
                WHERE filter_id IN ({$filterIds}) "; // 8554

        $data = $this->fetchAllAssoc($sql);
        if ($data === false) {
            return array(array());
        }else{
            foreach ($data as $key => $filter){
                $data[$key]['filterDetails'] = json_decode($data[$key]['filter_details'], true);
                unset($data[$key]['filter_details']);
            }
            return $data;
        }
    }
    
    public function setLastSelectedFilter($userId, $filterData){

        $filterId = false;
        //if filter_id is set 
        //return $filterData->filterIds;
        if(!empty($filterData['filterDetails']['standardFilters'])){
            //set the filter id that we use in setJobFilterId()
            $filterId = $filterData['filterDetails']['standardFilters'];
        }else{
            //else filter_id not set
            //find out if there is alredy a row with saved set to 0
            $sql = "SELECT f.filter_id
                    FROM 
                        filters f 
                    JOIN 
                        user_to_filter utf ON f.filter_id = utf.filter_id 
                    WHERE 
                        utf.user_id = :userId AND f.saved = :saved";
            $params = array(           
                ':userId' => $userId,
                ':saved' => 0
            ); 

            $data = $this->fetchOneAssoc($sql,$params);

            // if there is no saved row = 0 in filters for user
            if(empty($data)){
                $filterId = $this->createLastSelectedFilter($userId, $filterData);
            }else{
                
                $id = $data['filter_id'];
                $this->modifyLastSelectedFilter($id, $filterData);
                $filterId = $id;
                
            }
        }
        if(!empty($filterId)){
            
            $this->setJobFilterId($userId, $filterId);
        }
    }

    public function createLastSelectedFilter($username, $filterData)
    {
        $filterModel = $this->app->model("filter");
        $filterData["customFilter"] = 1;
        $createdId = $filterModel->createFilter($username, $filterData);
        return $createdId;
    }
    
    public function modifyLastSelectedFilter($filterId, $filterData)
    {
        $filterModel = $this->app->model("filter");
        $filterModel->modifyFilter($filterId, $filterData);
    }
    /**
     * update the job_jfi_id(job filter id) in the user table.
     * job_jfi_id is used to store the filter_id of the last saved filter
     * @param type $userId
     * @param type $filterId
     * @throws \Exception
     */
    private function setJobFilterId($userId, $filterId){
        if(empty($userId) || empty($filterId)){
            throw new \Exception ("Wrong Paramters");
        }
        $sql = "UPDATE 
                    users 
                SET 
                    user_jfi_id = :filterId 
                WHERE 
                    user_id = :userId;";
        $params = array(":filterId"=>$filterId, "userId"=>$userId);
        
        $rowsAffected = $this->update($sql, $params);
        if ($rowsAffected == 0) {
            throw new \Exception("Updated failed.");
        }
        
    }
    
    
    
    public function deleteFilter($id)
    {
        if (!isset($id)) {
            throw new \Exception("No ID given");
        }
        $sql = "DELETE dbo.user_to_filter WHERE filter_id = :filter_id";
        
        $this->insert($sql, array(":filter_id" => $id));
     
        $sql = "DELETE FROM dbo.filters WHERE filter_id = :filter_id";
        
        $deleted = $this->delete($sql, array(":filter_id" => $id));

        if ($deleted === 0) {
            return false;
        }
        if ($deleted === 1) {
            return true;
        }
        if ($deleted > 1) {
            // log something as more than one entry has been deleted
            throw new \Exception("Fatal error 682: SQL Exception, contact System Administrator");
        }
    }

    public function load(){}
    
    public function save(){}
}
