<?php

namespace App\Models;
use Elf\Application\Application;
use Elf\Exception\ConflictException;
use App\Utility\Helpers;


class ManualAdjustmentType extends \Elf\Db\AbstractAction {

    private $id;
    private $type;
    private $description;
    private $createdAt;
    private $updatedAt;
    private $deletedAt;
    private $amount;
    private $jobId;


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


    ///// All the setters and getters
    public function setFromArray($data)
    {
        foreach ($this->fieldMap as $key => $mapping) { //loop through the field map and only pick up data that we want
            $setMethod = "set" . ucfirst($key);
            if (method_exists($this, $setMethod) && isset($data[$mapping['name']])) { // check if we can actually update this field
                $this->$setMethod($data[$mapping['name']]);
            } else if (method_exists($this, $setMethod) && isset($data[$key])) { // check if we can actually update this field
                $this->$setMethod($data[$key]);
            }
        }
    }

    public function getById($id)
    {
        $this->setId($id);
        $this->load();
    }
    

    public function getAsArray($postCheck = null)
    {
        $returnArray = array();
        foreach ($this->fieldMap as $key => $mapping) { //loop through the field map and only pick up data that we want
            if ($postCheck == null) {
                $getMethod = "get" . ucfirst($key);
                if (method_exists($this, $getMethod) &&
                    (isset($mapping['expose']) &&
                        false !== $mapping['expose']) || !isset($mapping['expose'])
                ) { // check if we can actually update this field
                    $returnArray[$key] = $this->$getMethod();
                }
            } else {
                $getMethod = "get" . ucfirst($key);
                unset($this->tvcDelivery);
                if (method_exists($this, $getMethod) &&
                    (isset($mapping['expose']) &&
                        false !== $mapping['expose']) || !isset($mapping['expose']) &&
                    isset($this->$key)
                ) { // check if we can actually update this field
                    $returnArray[$key] = $this->$getMethod();
                }
            }
        }
        return $returnArray;
    }


    public function load() {
        if($this->getId()) {
            $params = array (
                ':id' => $this->id,
            );
            $sql = "SELECT
                           ma.*
                          ,mat.name as type
                    FROM dbo.manual_adjustments ma
                    LEFT JOIN
                        manual_adjustment_types mat
                    ON ma.type_id = mat.id
                    WHERE ma.id = :id";

            $data = $this->fetchOneAssoc($sql,$params);

            $this->setFromArray($data);
            return;
        }
    }

    /**
     * @return mixed
     */
    public function getJobId()
    {
        return $this->jobId;
    }

    /**
     * @param mixed $jobId
     */
    public function setJobId($jobId)
    {
        $this->jobId = (int)$jobId;
    }

    /**
     * @return mixed
     */
    public function getTypeId()
    {
        return $this->typeId;
    }

    /**
     * @param mixed $typeId
     */
    public function setTypeId($typeId)
    {
        $this->typeId = $typeId;
    }

    /**
     * @return mixed
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param mixed $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }
    
    /**
     * @return mixed
     */
    public function getCreatedAt()
    {
        return date("d-m-Y", strtotime($this->createdAt));
    }

    /**
     * @param mixed $createDate
     */
    public function setCreatedDate($createDate)
    {
        $this->createDate = date("Y-m-d", strtotime($createDate));
    }
    /**
     * @return mixed
     */
    public function getDeletedAt()
    {
        return date("d-m-Y", strtotime($this->deletedAt));
    }

    /**
     * @param mixed $deleteDate
     */
    public function setDeleteDate($deleteDate)
    {
        $this->deletedAt = date("Y-m-d", strtotime($deleteDate));
    }
    /**
     * @return mixed
     */
    public function getUpdatedAt()
    {
        return date("d-m-Y", strtotime($this->updatedAt));
    }

    
    /**
     * @param mixed $amount
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;
    }
    
    public function save(){
        
    }

}
