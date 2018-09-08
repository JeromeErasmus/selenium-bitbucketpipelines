<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Models;
use Elf\Application\Application;


class TvcCost extends \Elf\Db\AbstractAction {
    
    public $tablename = 'dbo.tvc_costs';
        
    protected $fieldMap = array(
        'tvcCostId' => array(
            'name' => 'tco_id',
            'type' => 'integer',
            'required' => false,
            'allowEmpty' => true
        ),
        'tvcTypeId' => array(
            'name' => 'tco_tty_id',
            'type' => 'integer',
            'required' => true,
            'allowEmpty' => false
        ),
        'tvcToAirId' => array(
            'name' => 'tco_tta_id',
            'type' => 'integer',
            'required' => true,
            'allowEmpty' => false
        ),
        'isInformercial' => array(
            'name'=> 'tco_is_infomercial',
            'type' => 'boolean',
            'required' => true,
            'allowEmpty' => false
        ),
        'isTvc' => array(
            'name'=> 'tco_is_tvc',
            'type' => 'boolean',
        ),
        'cost' => array(
            'name' => 'tco_cost',
            'type' => 'double',
            'required' => true,
            'allowEmpty' => false
        ),
        'comments' => array(
            'name' => 'tco_comments',
            'type' => 'string',
            'required' => true,
            'allowEmpty' => false
        ),
        'chargeCode' => array(
            'name' => 'tco_charge_code',
            'type' => 'string',
            'required' => true,
            'allowEmpty' => false
        ),
        'chargeCodeCharity' => array(
            'name' => 'tco_charge_code_charity',
            'type' => 'string',
            'required' => true,
            'allowEmpty' => false
        )
    );
    
    
    //Gets all TvcCosts
    public function getTvcCosts ()
    {
        $sql = "SELECT * FROM " .$this->tablename;
        $data = $this->fetchAllAssoc($sql);
        
        if (!empty($data)) {
            $tvcCosts = array();
            foreach ($data as $dataset) {
                $tvcCosts[] = $this->mapData($dataset);
            }
            return $tvcCosts;
        } else {
            return false;
        }
    }
    
    //Gets an individual TvcCost
    public function getTvcCostById ($id) 
    {
        $sql = "SELECT * 
            FROM " .$this->tablename.
            " WHERE 
            tco_id = :requestedId";
        
        $data = $this->fetchOneAssoc($sql,array(':requestedId' => $id));
        
        // Check for actual returned data, and then puts it back in an array format similar to what was input
        if (!empty($data)) {
            return $this->mapData($data);
        } else {
            return false;
        }        
    }
    
    //This function takes one data set and maps all the entries to have keys that 
    //match up to the input names, i.e. tco_id will be mapped to tvcCostId for readableness
    public function mapData($data) 
    {
        $fieldmapping = $this->fieldMap;
        $tvcData = array();
        foreach ($fieldmapping as $fieldname => $value){
                if ($fieldname == 'comments'){
                    $tvcData[$fieldname] = rtrim($data[$fieldmapping[$fieldname]['name']]);
                } else {
                    $tvcData[$fieldname] = $data[$fieldmapping[$fieldname]['name']];
                }
            }
        return $tvcData;
    }
    
    //Creates a new TvcCost and returns it's id 
    public function createTvcCost($tvcpostdata)
    {
        $sql = "INSERT INTO ".$this->tablename." (tco_tty_id,tco_tta_id,tco_is_infomercial,tco_is_tvc,tco_cost,tco_comments,tco_charge_code,tco_charge_code_charity) "
                . "VALUES(:typeId, :toAirId, :isInfomercial, :isTvc, :cost, :comments, :chargeCode, :chargeCodeCharity)";
        
        $params = array(
            ':typeId' => $tvcpostdata['tvcTypeId'],
            ':toAirId' => $tvcpostdata['tvcToAirId'],
            ':isInfomercial' => $tvcpostdata['isInformercial'],
            ':isTvc' => $tvcpostdata['isTvc'],
            ':cost' => $tvcpostdata['cost'],
            ':comments' => $tvcpostdata['comments'],
            ':chargeCode' => $tvcpostdata['chargeCode'],
            ':chargeCodeCharity' => $tvcpostdata['chargeCodeCharity']
        );
        try {
            $id = $this->insert($sql,$params);
        } catch (Exception $ex) {
            return false;
        }
        return $id; 
    }
    public function getLastId()
    {
        $sql = 'SELECT TOP 1 tco_id FROM dbo.tvc_costs ORDER BY tco_id DESC';
        $lastid = $this->fetchOneAssoc($sql,'');
        return $lastid;
    }
    //Modifies an existing tvc cost
    public function modifyTvcCost($id, $newdata)
    {
        if (empty($newdata)) {
            throw new \Exception("Nothing to modify");
        }
        
        $sql = "SELECT tco_id, tco_tty_id,tco_tta_id,tco_is_infomercial,tco_is_tvc,tco_cost,"
                . "tco_comments,tco_charge_code,tco_charge_code_charity "
                . "FROM " .$this->tablename. 
                " WHERE tco_id = :id";
        
        $existingData = $this->fetchOneAssoc($sql, array(":id" => $id));
        
        if (empty($existingData)) {
            throw new \Exception("Tvc Cost Id : " . $id . " does not exist");
        }
        $mapping = $this->fieldMap;
        
        $sql = "UPDATE ".$this->tablename." SET ";
        $setsql = "";
        foreach($newdata as $fieldName => $value) {
            if (!array_key_exists($fieldName, $mapping)) {       //if the input field doesn't exist in db
                throw new \Exception("Unknown field." . "<br/>");
            }
            $setsql .= "{$mapping[$fieldName]['name']} = :{$mapping[$fieldName]['name']}," ;
            $params[":{$mapping[$fieldName]['name']}"] = $value;
        }
        
        $setsql = rtrim($setsql, ',');
        $params[':tco_id'] = $id;
        if(!empty($setsql)){
            $sql .= $setsql;            
        }

        $sql .= " WHERE tco_id = :tco_id";
        
        try {
            $this->insert($sql, $params);
        } catch (Exception $ex) {
            echo "Error 101: {$ex->getMessage()}";      //kill it better
            return false;
        }
        return true;
    }
    
    public function validate(Array $tvcData)
    {
        if (!empty($tvcData['cost'])){
            $tvcData['cost'] = (double)$tvcData['cost'];
        }
        
        return parent::validate($tvcData);
    }
    
    public function deleteCostEntry($id) 
    {
        $sql = "DELETE FROM ".$this->tablename. 
                " WHERE tco_id = " .$id ;
        //die('the id is ' . $id);
        try {
            $this->insert($sql,'');
        } catch (Exception $ex) {
            return false;
        }
        return true;
    }

    public function load(){}
    
    public function save(){}
}
