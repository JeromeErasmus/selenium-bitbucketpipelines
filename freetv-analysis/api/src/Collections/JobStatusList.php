<?php

namespace App\Collections;

/**
 * Description of JobStatusList
 *
 * @author Adam
 */
class JobStatusList extends AppCollection implements AppCollectionInterface
{
    protected $params = array();
    protected $table = "dbo.job_statuses"; 
    protected $alias = "job_status";
    protected $list; // the returned list of results
    protected $joins = array();
    
    /**
     * fields to select / return
     * @var type 
     */
    protected $fieldMap = array(
        'statusId' => array(
            'name' => 'jst_id',
            'type' => 'integer',
        ),
        'statusName' => array(
            'name' => 'jst_name',
            'type' => 'string',
        ),
    );
   
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

        $data = $this->fetchAllAssoc($sql);  
        $this->list = $data;
        return $data;
    }

}
