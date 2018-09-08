<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Models;
use Elf\Application\Application;


class Filter extends \Elf\Db\AbstractAction {
    
    
    
    public function getFilterById($filterId)
    {
        $sql = "SELECT 
            filter_name, filter_details, created_at
            FROM
            dbo.filters
            WHERE
            filter_id = :id";
           
        $data = $this->fetchOneAssoc($sql,array(':id' => $filterId));

        if ($data === false) {
            return false;
        }
        $data['filterName'] = $data['filter_name'];
        unset($data['filter_name']);
        $data['createdAt'] = $data['created_at'];
        unset($data['created_at']);
        $data['filterDetails'] = json_decode($data['filter_details'], true);
        unset($data['filter_details']);

        return $data;
    }

    public function createFilter($username, $filterData)
    {
        $sql = "INSERT INTO
            dbo.filters(filter_name, filter_details, saved) VALUES
            (:filterName, :filterDetails, :saved)";

        $params = array(            //should probably validate the data fields first.
            ':filterName' => $filterData['filterName'],
            ':filterDetails' => json_encode($filterData['filterDetails'])
        );
        if(isset($filterData['customFilter']) && $filterData['customFilter']){
            $params[':saved'] = 0;
        }else{
            $params[':saved'] = 1;
        }
        //$this->insert($sql, $params);
        try {
            
            $id = $this->insert($sql, $params); 
            $sql = "INSERT INTO
                    dbo.user_to_filter(user_id, filter_id)
                    VALUES('$username', $id)";        //this should be safe.
            
            $this->insert($sql);
        } catch (\Exception $ex) {
            echo "Error 101: {$ex->getMessage()}";      //kill it better
            die();
        }

        return $id;
    }
    
    public function modifyFilter($id, $filterData)
    {
        
        if (empty($filterData)) {
            throw new \Exception ("Nothing to modify");
        }
        $sql = "SELECT filter_name, filter_details FROM dbo.filters WHERE
                filter_id = :id";
        $existingData = $this->fetchOneAssoc($sql, array(":id" => $id));

        if (empty($existingData)) {
            throw new \Exception("id doesn't exist");
        } else {
            $params[':filter_id'] = $id;
        }

        $existingData['filter_details'] = json_decode($existingData['filter_details'], true);

        //var_dump($existingData);die();

        /* changes the input json field names to the database column names */
        if (array_key_exists('filterDetails', $filterData)) {
            /* add in the changed data */
            $existingData['filter_details'] = array_replace_recursive(
                    $existingData['filter_details'], $filterData['filterDetails']);
        }

        if (isset($filterData['filterName'])) {
            $namesql = 'filter_name = :filter_name';
            $params[':filter_name'] = $filterData['filterName'];
        }
        
        if (isset($filterData['filterDetails'])) {
            $detailsql = "filter_details = :filter_details"; 
            $params[':filter_details'] = json_encode($existingData['filter_details']);
        }
        


        $sql = "UPDATE dbo.filters SET ";
        if(!empty($namesql)){
            $sql .= $namesql;            
        }
        if(!empty($namesql) && !empty($detailsql)) {
            $sql .= ',';
        }
        if (!empty($detailsql)) {
            $sql .= $detailsql;            
        }
        
        $sql .= " WHERE filter_id = :filter_id";
        
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
        else if($id <= LAST_PREBUILT_FILTER){
            //cannot delete prebuilt filters
            return false;
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
            throw new \Exception("Fatal error 682");
        }
    }

    public function load(){}
    
    public function save(){}
}
