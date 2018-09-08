<?php
/**
 * Created by PhpStorm.
 * User: mchan
 * Date: 17/05/16
 * Time: 12:41 PM
 */

namespace App\Models;


use Elf\Db\AbstractAction;
use Elf\Exception\NotFoundException;
use Elf\Exception\ConflictException;
use App\Utility\Helpers;
use Elf\Utility\Convert;

class ChargeCode extends AbstractAction
{

    private $chargeCodeId;
    private $submittedDate;
    private $charityStatus;
    private $chargeCode;
    private $description;
    private $billingRate;
    private $typeCode;
    private $discount;
    private $mr;
    private $active;
    private $effectiveFrom;
    private $deletedAt;
    private $lateFee;
    private $isCharity;
    private $OASDisplayName;
    private $visibleInOAS;
    private $additionalConditions;
    private $GST;
    private $exGST;
    private $turnaroundTime;
    private $excludeActiveCheck = null;

    protected $fieldMap = [
        'chargeCodeId' => array(
            'name' => 'cco_id',
            'type' => 'integer',
            'required' => true,
            'allowEmpty' => false
        ),
        'chargeCode' => array(
            'name' => 'cco_charge_code',
            'type' => 'string',
            'required' => true,
            'allowEmpty' => false
        ),
        'description' => array(
            'name' => 'cco_description',
            'type' => 'string',
            'required' => true,
            'allowEmpty' => false
        ),
        'billingRate' => array(
            'name' => 'cco_billing_rate',
            'type' => 'double',
            'required' => true,
            'allowEmpty' => false,
            'float' => true,
        ),
        'typeCode' => array(
            'name' => 'cco_type_code',
            'type' => 'string',
            'required' => true,
            'allowEmpty' => false
        ),
        'discount' => array(
            'name' => 'cco_discount',
            'type' => 'double',
            'required' => true,
            'allowEmpty' => false,
            'float' => true,
        ),
        'mr' => array(
            'name' => 'cco_mr',
            'type' => 'string',
            'required' => true,
            'allowEmpty' => false
        ),
        'active' => array(
            'name' => 'cco_active',
            'type' => 'integer',
            'required' => true,
            'allowEmpty' => false
        ),
        'effectiveFrom' => array(
            'name' => 'cco_effective_from',
            'type' => 'string',
            'required' => true,
            'allowEmpty' => false
        ),
        'lateFee' => array(
            'name' => 'cco_late_fee',
            'type' => 'integer',
            'required' => true,
            'allowEmpty' => false,
            'float' => true,
        ),
        'deletedAt' => array(
            'name' => 'deleted_at',
            'type' => 'string',
            'required' => false,
            'allowEmpty' => true
        ),
        'isCharity' => array(
            'name' => 'cco_is_charity',
            'type' => 'int',
            'required' => true,
            'allowEmpty' => false
        ),
        'OASDisplayName' => array(
            'name' => 'cco_oas_display_name',
            'type' => 'string',
            'required' => false,
            'allowEmpty' => false
        ),
        'visibleInOAS' => array(
            'name' => 'cco_visible_in_oas',
            'type' => 'boolean',
            'required' => false,
            'allowEmpty' => false
        ),
        'turnaroundTime' => array(
            'name' => 'cco_turnaround',
            'type' => 'string',
            'required' => false,
            'allowEmpty' => false
        )
    ];

    /**
     * @return mixed
     */
    public function getChargeCodeId()
    {
        return $this->chargeCodeId;
    }

    /**
     * @param mixed $chargeCodeId
     */
    public function setChargeCodeId($chargeCodeId)
    {
        $this->chargeCodeId = $chargeCodeId;
    }

    /**
     * @return mixed
     */
    public function getSubmittedDate()
    {
        return $this->submittedDate;
    }

    /**
     * @param mixed $submittedDate
     */
    public function setSubmittedDate($submittedDate)
    {
        $this->submittedDate = $submittedDate;
    }

    /**
     * @return mixed
     */
    public function getCharityStatus()
    {
        return $this->charityStatus;
    }

    /**
     * @param mixed $charityStatus
     */
    public function setCharityStatus($charityStatus)
    {
        $this->charityStatus = $charityStatus;
        $ccoIsCharity = $this->charityStatus ? 1 : 0;
        $this->additionalConditions = " AND cco_is_charity = $ccoIsCharity ";
    }

    /**
     * @return mixed
     */
    public function getChargeCode()
    {
        return $this->chargeCode;
    }

    /**
     * @param mixed $chargeCode
     */
    public function setChargeCode($chargeCode)
    {
        $this->chargeCode = $chargeCode;
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
    public function getBillingRate()
    {
        return $this->billingRate;
    }

    /**
     * @param mixed $billingRate
     */
    public function setBillingRate($billingRate)
    {
        $this->billingRate = $billingRate;
//        $GST = ( ( ($billingRate * 100) * 10) / 100 ) / 100;
        $billingRate = str_replace( '/[, ]/', '', $billingRate );
        $GST = bcdiv ( bcdiv ( bcdiv ( bcmul ( $billingRate, 100 ), 11 ), 100 ), 100 );
        $this->GST = $GST;
        $this->exGST = $billingRate - $GST;
    }

    /**
     * @return mixed
     */
    public function getTypeCode()
    {
        return $this->typeCode;
    }

    /**
     * @param mixed $typeCode
     */
    public function setTypeCode($typeCode)
    {
        $this->typeCode = $typeCode;
    }

    /**
     * @return mixed
     */
    public function getDiscount()
    {
        return $this->discount;
    }

    /**
     * @param mixed $discount
     */
    public function setDiscount($discount)
    {
        $this->discount = $discount;
    }

    /**
     * @return mixed
     */
    public function getMr()
    {
        return $this->mr;
    }

    /**
     * @param mixed $mr
     */
    public function setMr($mr)
    {
        $this->mr = $mr;
    }

    /**
     * @return mixed
     */
    public function getActive()
    {
        return $this->active;
    }

    /**
     * @param mixed $active
     */
    public function setActive($active)
    {
        $this->active = $active;
    }

    /**
     * @return mixed
     */
    public function getEffectiveFrom()
    {
        return $this->effectiveFrom;
    }

    /**
     * @param mixed $effectiveFrom
     */
    public function setEffectiveFrom($effectiveFrom)
    {
        $this->effectiveFrom = $effectiveFrom;
    }

    /**
     * @return mixed
     */
    public function getDeletedAt()
    {
        return $this->deletedAt;
    }

    /**
     * @param mixed $deletedAt
     */
    public function setDeletedAt($deletedAt)
    {
        $this->deletedAt = $deletedAt;
    }

    /**
     * @return mixed
     */
    public function getLateFee()
    {
        return $this->lateFee;
    }

    /**
     * @param mixed $lateFee
     */
    public function setLateFee($lateFee)
    {
        $this->lateFee = $lateFee;
    }

    /**
     * @return mixed
     */
    public function getIsCharity()
    {
        return $this->isCharity;
    }

    /**
     * @param mixed $isCharity
     */
    public function setIsCharity($isCharity)
    {
        $this->isCharity = $isCharity;
    }

    /**
     * @return mixed
     */
    public function getOASDisplayName()
    {
        return $this->OASDisplayName;
    }

    /**
     * @param mixed $OASDisplayName
     */
    public function setOASDisplayName($OASDisplayName)
    {
        $this->OASDisplayName = $OASDisplayName;
    }

    /**
     * @return mixed
     */
    public function getVisibleInOAS()
    {
        return $this->visibleInOAS;
    }

    /**
     * @param mixed $visibleInOAS
     */
    public function setVisibleInOAS($visibleInOAS)
    {
        $this->visibleInOAS = $visibleInOAS;
    }

    /**
     * @return null
     */
    public function getExcludeActiveCheck()
    {
        return $this->excludeActiveCheck;
    }

    /**
     * @param null $excludeActiveCheck
     */
    public function setExcludeActiveCheck($excludeActiveCheck)
    {
        $this->excludeActiveCheck = $excludeActiveCheck;
    }

    

    /**
     * Get's all relevant Charge Codes based on the submitted date sent through in the query
     *
     * @throws NotFoundException
     * @throws \Exception
     */
    public function getEffectiveChargeCodes(){

        if($this->excludeActiveCheck === null) {
            $this->additionalConditions = ' AND cco_active = 1 ';
        }

        $sql = "
                SELECT [cco_charge_code]
                      ,[cco_description]
                      ,[cco_billing_rate]
                      ,[cco_type_code]
                      ,[cco_discount]
                      ,cco_id
                      ,cco_effective_from
                      ,[cco_mr]
                      ,[cco_active]
                      ,[cco_late_fee]
                      ,[deleted_at]
                      ,[cco_is_charity]
                      ,[cco_oas_display_name]
                      ,[cco_visible_in_oas]
                      ,[cco_turnaround]
                  FROM [charge_codes]
                  INNER JOIN (SELECT cco_charge_code as cc, MAX(cco_effective_from) as cc_effective_from
                                FROM charge_codes
                                WHERE cco_effective_from <= :submitted_date
                                GROUP BY cco_charge_code)
                            cc on cc.cc = cco_charge_code
                            AND cc.cc_effective_from = cco_effective_from
                  WHERE 
                  deleted_at is NULL
                  {$this->additionalConditions}

                  ORDER BY cco_charge_code, cco_effective_from DESC
            ";


        $params = array(
            ':submitted_date' => $this->submittedDate
        );

        $data = $this->fetchAllAssoc($sql, $params);

        if(!empty($data)){
            return $this->setFromArray($data);
        }
        throw new NotFoundException("entity not found");

    }

    /**
     * Gets all editable charge code that should appear in the maintenance screens
     *
     * @throws NotFoundException
     * @throws \Exception
     */
    public function getAllEditableChargeCodes(){
        $sql = "SELECT * FROM charge_codes
                WHERE (cco_active = 0 OR cco_effective_from >= getdate())
                AND deleted_at is NULL
                ";

        $data = $this->fetchAllAssoc($sql);

        if(!empty($data)){
            return $this->setFromArray($data);
        }
        throw new NotFoundException("entity not found");

    }

    public function restrictForOASUsers()
    {
        $this->additionalConditions = " AND cco_visible_in_oas = 1 ";
    }

    public function load()
    {
        if (!$this->getChargeCodeId()) {
            throw new \Exception("Charge Code ID not set");
        }

        $params = array(
            ':chargeCodeId' => $this->getChargeCodeId(),
        );

        $sql = "SELECT * FROM charge_codes where cco_id= :chargeCodeId";

        $data = $this->fetchOneAssoc($sql, $params);

        if(!empty($data))
        {
            $this->setSingleArray($data);

            return $this->getAsArray();
        }
        throw new NotFoundException("entity not found");
    }

    public function save()
    {
        $chargeCodeId = $this->getChargeCodeId();

        $date = new \DateTime();
        $currentTime = $date->format('Y-m-d H:i:s');

        if($this->effectiveFrom <= $currentTime){
            throw new NotFoundException("Effective from Date invalid");
        }

        if (!empty($chargeCodeId)) {
            return $this->updateRecord();
        } else {
            return $this->createRecord();
        }
    }

    public function deleteRecord()
    {
        if(empty($this->chargeCodeId)) {
            throw new NotFoundException("Charge Code ID not set");
        }
        $sql = "
        UPDATE [charge_codes]
           SET [cco_charge_code] = :chargeCode
              ,[cco_description] = :description
              ,[cco_billing_rate] = :billing_rate
              ,[cco_type_code] = :type_code
              ,[cco_discount] = :discount
              ,[cco_mr] = :mr
              ,[cco_active] = :active
              ,[cco_effective_from] = :effective_from
              ,[cco_late_fee] = :late_fee
              ,[deleted_at] = getdate()
              ,[cco_is_charity] = :is_charity
              ,[cco_oas_display_name] = :oas_display_name
              ,[cco_visible_in_oas] = :visible_in_oas
              ,[cco_turnaround] = :turnaround
         WHERE [cco_id] = :chargeCodeId
        ";

        $params = array(
            ':chargeCodeId' => $this->chargeCodeId,
            ':chargeCode' => $this->chargeCode,
            ':description' => $this->description,
            ':billing_rate' => $this->billingRate,
            ':type_code' => $this->typeCode,
            ':discount' => $this->discount,
            ':mr' => $this->mr,
            ':active' => $this->active,
            ':effective_from' => $this->effectiveFrom,
            ':late_fee' => $this->lateFee,
            ':is_charity' => $this->isCharity,
            ':oas_display_name' => $this->OASDisplayName,
            ':visible_in_oas' => $this->visibleInOAS,
        );

        $this->update($sql, $params);

        return true;

    }

    public function updateRecord()
    {
        if(empty($this->chargeCodeId)) {
            throw new NotFoundException("Charge Code ID not set");
        }

        $sql = "
        UPDATE [charge_codes]
           SET [cco_charge_code] = :chargeCode
              ,[cco_description] = :description
              ,[cco_billing_rate] = :billing_rate
              ,[cco_type_code] = :type_code
              ,[cco_discount] = :discount
              ,[cco_mr] = :mr
              ,[cco_active] = :active
              ,[cco_effective_from] = :effective_from
              ,[cco_late_fee] = :late_fee
              ,[cco_is_charity] = :is_charity
              ,[cco_oas_display_name] = :oas_display_name
              ,[cco_visible_in_oas] = :visible_in_oas
              ,[cco_ex_gst] = :exGST
              ,[cco_GST] = :GST
              ,[cco_turnaround] = :turnaround
         WHERE [cco_id] = :chargeCodeId
        ";

        $params = array(
            ':chargeCodeId' => $this->chargeCodeId,
            ':chargeCode' => $this->chargeCode,
            ':description' => $this->description,
            ':billing_rate' => $this->billingRate,
            ':type_code' => $this->typeCode,
            ':discount' => $this->discount,
            ':mr' => $this->mr,
            ':active' => $this->active,
            ':effective_from' => $this->effectiveFrom,
            ':late_fee' => $this->lateFee,
            ':is_charity' => $this->isCharity,
            ':oas_display_name' => $this->OASDisplayName,
            ':visible_in_oas' => $this->visibleInOAS,
            ':GST' => $this->GST,
            ':exGST' => $this->exGST,
            ':turnaround' => $this->turnaround,
        );

        $this->update($sql, $params);

        return true;

    }

    public function createRecord(){
        $sql = "
            INSERT INTO [charge_codes]
               ([cco_charge_code]
               ,[cco_description]
               ,[cco_billing_rate]
               ,[cco_type_code]
               ,[cco_discount]
               ,[cco_mr]
               ,[cco_active]
               ,[cco_effective_from]
               ,[cco_late_fee]
               ,[cco_is_charity]
               ,[cco_oas_display_name]
               ,[cco_visible_in_oas]
               ,[cco_ex_gst] = :exGST
               ,[cco_GST] = :GST
              ,[cco_turnaround]
               )
         VALUES
               (:charge_code,
               :description,
               :billing_rate,
               :type_code,
               :discount,
               :mr,
               :active,
               :effective_from,
               :late_fee,
               :is_charity,
               :oas_display_name,
               :visible_in_oas
               :GST
               :exGST
               :turnaround
               )
           ";

        $params = array(
            ':charge_code' => $this->chargeCode,
            ':description' => $this->description,
            ':billing_rate' => $this->billingRate,
            ':type_code' => $this->typeCode,
            ':discount' => $this->discount,
            ':mr' => $this->mr,
            ':active' => $this->active,
            ':effective_from' => $this->effectiveFrom,
            ':late_fee' => $this->lateFee,
            ':is_charity' => $this->isCharity,
            ':oas_display_name' => $this->OASDisplayName,
            ':visible_in_oas' => $this->visibleInOAS,
            ':GST' => $this->GST,
            ':exGST' => $this->exGST,
            ':turnaround' => $this->turnaround,
        );

        $id = $this->insert($sql,$params);

        $this->chargeCodeId = $id;
        return $id;
    }

    public function getAsArray() {
        $returnArray = array();
        foreach ($this->fieldMap as $key => $mapping) { //loop through the field map and only pick up data that we want

            $getMethod = "get" . ucfirst($key);

            if (method_exists($this, $getMethod) &&
                (isset($mapping['expose']) &&
                    false !== $mapping['expose']) || !isset($mapping['expose'])
            ) { // check if we can actually update this field
                $returnArray[$key] = $this->$getMethod();
            }
        }

        return $returnArray;
    }

    public function setSingleArray($data){
        foreach($this->fieldMap as $key => $mapping) { //loop through the field map and only pick up data that we want
            $setMethod = "set".$key;

            if(method_exists($this, $setMethod) && isset($data[$mapping['name']])) { // check if we can actually update this field
                $this->$setMethod($data[$mapping['name']]);
            }
        }
    }

    public function setSingleInputArray($data){
        foreach($this->fieldMap as $key => $mapping) { //loop through the field map and only pick up data that we want
            $setMethod = "set".$key;

            if(method_exists($this, $setMethod) && isset($data[$key])) { // check if we can actually update this field
                $this->$setMethod($data[$key]);
            }
        }
    }

    public function setFromArray($data) {

        foreach($data as $chargeCodeArrayKey => $chargeCode) {
            $id = $chargeCode['cco_id'];
            $chargeCodeList[$chargeCodeArrayKey] = array();

            $keys = array_keys($chargeCode);
            foreach($this->fieldMap as $fieldName => $details) {
                if (in_array($details['name'], $keys)) {
                    if (isset($details['type']) && $details['type'] == 'boolean') {
                        $chargeCodeList[$chargeCodeArrayKey][$fieldName] = Convert::toBoolean($chargeCode[$details['name']]);
                    }
                    elseif(isset($details['float']) && $details['float'] == true){
                        $chargeCodeList[$chargeCodeArrayKey][$fieldName] = number_format($chargeCode[$details['name']], 2);
                    }
                    else {
                        $chargeCodeList[$chargeCodeArrayKey][$fieldName] = $chargeCode[$details['name']];
                    }
                }
            }
        }
        return $chargeCodeList;
    }

    /**
     * @return mixed
     */
    public function getTurnaroundTime()
    {
        return $this->turnaroundTime;
    }

    /**
     * @param mixed $turnaroundTime
     */
    public function setTurnaroundTime($turnaroundTime)
    {
        $this->turnaroundTime = $turnaroundTime;
    }


}