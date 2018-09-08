<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Collections;

use Elf\Db\AbstractCollection;

/**
 * Description of AppCollection
 *
 * @author michael
 */
class AppCollection extends AbstractCollection
{
    //put your code here
    /**
     * 
     * @return type
     */
    protected function getFilterSql()
    {
        $sql = "";
        $i = 0;
        foreach($this->params as $field => $value) {
            if($i === 0) {
                $sql .= " WHERE ";
                $i += 1;
            } else {
                $sql .= " AND ";
                $i += 1;
            }
            $sql .= " {$this->fieldMap[$field]['name']} = $value ";
        }
        return $sql;
    }
    
    /**
     * query build for the joinds
     * @return type
     */
    protected function getJoinsSql()
    {
        $joins = "";
        foreach($this->joins as $join => $joinInfo) {
            $joins .= " {$joinInfo['join']} JOIN dbo.{$join} as {$joinInfo['alias']} ON {$joinInfo['alias']}.{$joinInfo['pk']} = {$this->alias}.{$joinInfo['fk']} ";
        }
        return $joins;
    }
    
    /**
     * iterates through fieldmap and joins fields for selection
     * @return type
     */
    protected function getFieldSql()
    {
        $fields = array();
        foreach($this->fieldMap as $field => $mapping) {
            $fields[] = " {$this->alias}.{$mapping['name']} as $field ";
        }
        if(empty($this->joins)){
            return implode(',', $fields);
        }
        foreach($this->joins as $join) {
            foreach($join['fields'] as $field => $alias) {
                $fields[] = " {$join['alias']}.{$field} as {$alias} ";
            }
        }
        return implode(',', $fields);
    }   
    
    /**
     * @TODO move this to astract parent
     * @param type $value
     * @param type $mappingInfo
     * @return type
     */
    protected function validateField($value, $mappingInfo)
    {
        switch($mappingInfo['type']) {
            case "string" :
                return filter_var($value, FILTER_SANITIZE_STRING);
            case "integer" :
                return filter_var($value, FILTER_VALIDATE_INT);
            default :
                return filter_var($value);
        }
    }
    
    /**
     * 
     */
    public function fetch(){}


    
    /** 
     * @return type
     */
    public function getList()
    {
        return $this->list;
    }
    
     /**
     * 
     * @param type $params
     */
    public function setParams($params = array())
    {
        foreach($this->fieldMap as $field => $mapping) {
            if(isset($params[$field]) && false !== $this->validateField($params[$field], $mapping)) {
                $this->params[$field] = $params[$field];
            }
        }
    }

    
}
