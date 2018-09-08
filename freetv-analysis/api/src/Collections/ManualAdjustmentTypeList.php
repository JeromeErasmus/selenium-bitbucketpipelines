<?php
namespace App\Collections;
use \Elf\Db\AbstractCollection;

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
    class ManualAdjustmentTypeList extends AppCollection implements AppCollectionInterface{

        protected $params = array();
        protected $table = "dbo.manual_adjustment_types";
        protected $alias = "mat";
        protected $list;
        
        protected $fieldMap = array (
            'id' => array (
                'name' => 'id',
                'type' => 'integer',
                'required' => false,
                'allowEmpty' => true,
            ),
            'type' => array (
                'name' => 'type',
                'type' => 'string',
                'required' => false,
            ),

        );

        protected $joins = array();


        /**
     * 
     * @return type
     */
    public function getAll()     //don't think this is needed
    {

        $sql = "SELECT {$this->getFieldSql()} FROM {$this->table} as {$this->alias} ";   
        $sql .= $this->getJoinsSql();
        if(empty($this->params)) {
            $sql = str_replace("SELECT ", "SELECT TOP 100 ", $sql);
        } else {
            $sql .= $this->getFilterSql();
        }

        $sql .= " ORDER BY mat.id";

        $data = $this->fetchAllAssoc($sql);
        $this->list = $data;
        return $data;
    }
}