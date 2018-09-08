<?php

namespace App\Collections;
use App\Models\EloquentModel;
use \Elf\Db\AbstractCollection;
use App\Models\TvcRequirement;
use App\Models\Requirement;
use App\Models\ChargeCode;
use Elf\Exception\NotFoundException;

/**
 * Description of KeyNumberList
 *
 * @author Adam
 */
class KeyNumberList extends AppCollection implements AppCollectionInterface
{
    protected $params = array();
    protected $table = "dbo.tvcs";
    protected $alias = "tvc";
    protected $advertiserCharityStatus = 0;
    protected $deleted;
    protected $list; // the returned list of results
    protected $keyNumberIsValid; // A state variable used when validating multiple key numbers
    protected $chargeCodeModel; // A state variable used when validating multiple key numbers

    /**
     * fields to select / return
     * @var type
     */
    protected $fieldMap = array(
        'tvcId' => array(
            'name' => 'tvc_id',
            'type' => 'integer',
        ),
        'jobId' => array(
            'name' => 'tvc_job_id',
            'type' => 'integer',
        ),
        'chargeCode' => array(
            'name' => 'tvc_charge_code',
            'displayName' => 'Charge Code',
            'type' => 'string',
        ),
        'expiryDate' => array(
            'name' => 'tvc_expiry_date',
            'displayName' => 'Expiry Date',
            'type' => 'string',
            'ignoreForValidation' => true,
        ),
        'assignedDate' => array(
            'name' => 'tvc_cad_assigned_date',
            'displayName' => 'Assigned Date',
            'type' => 'string',
            'ignoreForValidation' => true,
        ),
        'keyNumber' => array(
            'name' => 'tvc_key_no',
            'displayName' => 'Key Number',
            'type' => 'string',
        ),
        'description' => array (
            'name' => 'tvc_product_description',
            'displayName' => 'Description',
            'type' => 'string',
        ),
        'length' => array (
            'name' => 'tvc_length',
            'displayName' => 'Length',
            'type' => 'string',
        ),
        'op48' => array (
            'name' => 'tvc_op48',
            'displayName' => 'OP48',
            'type' => 'string',
        ),
        'productCategoryCode' => array (
            'name' => 'tvc_product_category_code',
            'displayName' => 'Advertiser/Product Category Code',
            'type' => 'string',
        ),
        'cadNumber' => array(
            'name' => 'tvc_cad_no',
            'type' => 'string',
            'ignoreForValidation' => true,
        ),
        'classificationCode' => array(
            'name' => 'tvc_classification_code',
            'displayName' => 'Classification',
            'type' => 'string',
        ),
        'contentCode' => array(
            'name' => 'tvc_content_code',
            'displayName' => 'Content Code',
            'type' => 'string',
        ),
        'lateFee' => array(
            'name' => 'tvc_late_fee',
            'displayName' => 'Priority Processing Fee',
            'type' => 'integer',
            'ignoreForValidation' => true,
        ),
        'eventType' => array(
            'name' => 'tvc_event_type',
            'displayName' => 'Event Type',
            'type' => 'string',
        ),
        'rejectionProcessed' => array(
            'name' => 'tvc_rejection_processed',
            'type' => 'integer',
            'ignoreForValidation' => true,
        ),
        'tvcPaymentFailure' => array(
            'name' => 'tvc_payment_failure',
            'type' => 'integer',
            'ignoreForValidation' => true,
        ),
        'originalTVC' => array(
            'name' => 'tvc_original_tvc_id',
            'type' => 'integer',
            'ignoreForValidation' => true,
        ),
        'originalKeyNumber' => array (
            'name' => 'tvc_original_key_number',
            'type' => 'string',
            'required' => false,
            'allowEmpty' => true,
            'ignoreForValidation' => true,
        ),
        'cTime' => array(
            'name' => 'tvc_ctime',
            'displayName' => 'C. Time',
            'type' => 'integer',
            'ignoreForValidation' => true,
        ),
        'tvcToAir' => array (
            'name' => 'tvc_to_air',
            'type' => 'string',
            'ignoreForValidation' => true,
        ),
        'tvcType' => array (
            'name' => 'tvc_type',
            'type' => 'string',
            'ignoreForValidation' => true,
        ),
    );

    /**
     * define joins tables and fields
     * @var type
     */
    protected $joins = array(
        'tvc_formats' => array(
            'alias' => 'b',
            'join' => 'LEFT',
            'pk' => 'tfo_id',
            'fk' => 'tvc_format_code',
            'fields' => array(
                'tfo_name' => 'tvcDelivery',
                'tfo_internal_name' => 'tvcDeliveryValue',
            ),
        ),
    );

    public function setDeleted($deleted){
        $this->deleted = $deleted;
    }

    /**
     * Get key numbers , with or without requirements
     * @param bool $withRequirements
     * @return array|bool
     */
    public function getAll($withRequirements = false)
    {
        $sql = "SELECT {$this->getFieldSql()} FROM {$this->table} as {$this->alias} ";

        $sql .= $this->getJoinsSql();
        if(empty($this->params)) {
            $sql = str_replace("SELECT ", "SELECT TOP 100 ", $sql);
        } else {
            $sql .= $this->getFilterSql();
        }

        /*
         * true: returns only jobs that have been deleted
         * false: returns only jobs that have not been deleted
         * NULL: returns all jobs
         */
        if(isset($this->deleted) && $this->deleted == 0){
            $sql .= " AND {$this->alias}.deleted_at IS NULL  ";
        }
        elseif(isset($this->deleted) && $this->deleted == 1){
            $sql .= " AND {$this->alias}.deleted_at IS NOT NULL ";
        }

        $sql .= " ORDER BY tvcId";

        $data = $this->fetchAllAssoc($sql);
        $this->list = $data;

        if ($withRequirements == true) {
            $this->getRequirementsForKeyNumbers();
        }

        return $this->list;
    }

    /**
     * Retrieves all key numbers that match the set params and then validates them based on their requirements and the key numbers them selves
     *
     * It returns an array of key numbers that have not been withdrawn and not been assigned CAD numbers with whether they are valid
     * and a list of reasons why they are not valid if not.
     * @return array
     */
    public function validateKeyNumbers($jobId = null, $override = false)
    {
        $keyNumbers = $this->getAll();
        if($jobId == null) {
            $jobId = $this->app->request->query('jobId');
        }

        // Retrieve the job, and from there, the advertiser's charity status for later validation against charge codes
        $job = $this->app->model('job');
        $job->setJobId($jobId);
        $job->load();
        $jobData = $job->getFullJob();
        $this->advertiserCharityStatus = $jobData['advertiser']['authorisedCharity'];

        $capsule = $this->app->service('eloquent')->getCapsule();
        $tvcRequirementModel = new TvcRequirement();
        $requirementModel = new Requirement();
        $this->chargeCodeModel = $this->app->model('chargeCode');
        $cadAssignableKeyNumbers = array();

        foreach($keyNumbers as $index => $keyNumber) {
            // if the key number is not
            // 1. Cad Assigned
            // 2. Withdrawn
            // 3. Rejected and had it's rejection processed
            // 4. Marked as having failed it's transaction
            // assess it to see if it's ready to be processed

            $expiryCheck = $this->checkForOriginalKeyNumberExpiry($keyNumber['originalTVC']);
            if(empty($keyNumber['cadNumber'])
                && $keyNumber['eventType'] != 'W'
                && !($keyNumber['eventType'] == 'R'
                    && $keyNumber['rejectionProcessed'])
                && empty($keyNumber['tvcPaymentFailure'])
                && $expiryCheck === true
            ) {
                $this->keyNumberIsValid = true;
                $keyNumberRequirementCollection = $tvcRequirementModel->where('rtv_tvc_id',$keyNumber['tvcId'])->with('listRequirements')->get();
                $keyNumberRequirements = array();
                foreach ($keyNumberRequirementCollection as $item) {
                    $tmp = $tvcRequirementModel->convertUsingFieldMap($item);
                    $tmp['requirement'] = $requirementModel->convertUsingFieldMap($item['listRequirements'][0]);
                    $keyNumberRequirements[] = $tmp;
                }
                if($keyNumber['eventType'] == 'R') {
                    $cadAssignableKeyNumbers[] = array (
                        $keyNumber,
                        'requirements' => $keyNumberRequirements,
                        'valid' => array(
                            'reasons' => array(),
                            'valid' => $this->keyNumberIsValid,
                        ),
                    );
                } else {
                    $cadAssignableKeyNumbers[] = array (
                        $keyNumber,
                        'requirements' => $keyNumberRequirements,
                        'valid' => array(
                            'reasons' => array_merge($this->validateKeyNumberRequirements($keyNumberRequirements), $this->validateKeyNumber($keyNumber)),
                            'valid' => $this->keyNumberIsValid,
                        ),
                    );
                }
            }
            else if($override === true){
                $keyNumberRequirementCollection = $tvcRequirementModel->where('rtv_tvc_id',$keyNumber['tvcId'])->with('listRequirements')->get();
                $keyNumberRequirements = array();
                foreach ($keyNumberRequirementCollection as $item) {
                    $tmp = $tvcRequirementModel->convertUsingFieldMap($item);
                    $tmp['requirement'] = $requirementModel->convertUsingFieldMap($item['listRequirements'][0]);
                    $keyNumberRequirements[] = $tmp;
                }

                $cadAssignableKeyNumbers[] = array (
                    $keyNumber,
                    'requirements' => $keyNumberRequirements,
                    'valid' => array(
                        'reasons' => array_merge($this->validateKeyNumberRequirements($keyNumberRequirements), $this->validateKeyNumber($keyNumber)),
                        'valid' => true,
                    ),
                );
            }

        }
        return $cadAssignableKeyNumbers;
    }

    /**
     * Validate a key numbers requirements, checking to see if that all requirements marked as mandatory are satisfied,
     * and returning which ones are not satisfied if they exist.
     * @param $keyNumberRequirements
     * @return mixed
     */
    public function validateKeyNumberRequirements($keyNumberRequirements)
    {
        $reasonsWhyKeyNumberIsInvalid = array();
        foreach($keyNumberRequirements as $keyNumberRequirement) {
            if($keyNumberRequirement['mandatory'] == true && $keyNumberRequirement['satisfied'] == false) {
                $this->keyNumberIsValid = false;
                $reasonsWhyKeyNumberIsInvalid[] = ($keyNumberRequirement['requirement']['shortDescription'] ? "'" .  $keyNumberRequirement['requirement']['shortDescription']  . "'" : "A requirement") . ' has not been satisfied';
            }
        }

        return $reasonsWhyKeyNumberIsInvalid;
    }

    /**
     * Validates a key number, checking that all the fields necessary for a key number are present and set and returns error messages if they aren't
     * @param $keyNumber
     * @return array
     */
    public function validateKeyNumber($keyNumber)
    {
        $reasonsWhyKeyNumberIsInvalid = array();
        foreach ($this->fieldMap as $key => $field) {
            if(empty($field['ignoreForValidation'])) {
                // Validate that the field has a value
                if( $key == 'classificationCode' && empty($keyNumber[$key])){
                    $this->keyNumberIsValid = false;
                    $reasonsWhyKeyNumberIsInvalid[] = $field['displayName'] . ' has not been set';
                }

                if (!isset($keyNumber[$key])) {
                    $this->keyNumberIsValid = false;
                    $reasonsWhyKeyNumberIsInvalid[] = $field['displayName'] . ' has not been set';
                    // Validate that the charge code matches the type of advertiser (i.e. charity charge code for charity advertiser)
                } elseif ($key == 'chargeCode') {
                    $this->chargeCodeModel->setChargeCodeId($keyNumber[$key]);
                    try {
                        $chargeCode = $this->chargeCodeModel->load();
                        if($chargeCode['isCharity'] != $this->advertiserCharityStatus) {
                            $this->keyNumberIsValid = false;
                            $reasonsWhyKeyNumberIsInvalid[] = 'Invalid charge code has been set - ' . ($this->advertiserCharityStatus ? 'Charity charge code required' : 'Non-charity charge code required');
                        }
                    } catch (NotFoundException $e) {
                        $this->keyNumberIsValid = false;
                        $reasonsWhyKeyNumberIsInvalid[] = 'Please select a charge code';
                    }
                } elseif (($key == 'op48') || ($key == 'cTime')) {
                    if(empty($keyNumber[$key])){
                        $this->keyNumberIsValid = false;
                        $reasonsWhyKeyNumberIsInvalid[] = 'Invalid selection has been made for ' . $field['displayName'] ;
                    }
                }
            }
        }
        return $reasonsWhyKeyNumberIsInvalid;
    }

    private function checkForOriginalKeyNumberExpiry($originalTVCId){
        if(empty($originalTVCId)){
            return true;
        }
        $sql = "SELECT DATEADD(year, 2, tvc_cad_assigned_date) as expiryDate
                FROM dbo.tvcs
                WHERE tvc_id = :tvcId";

        $data = $this->fetchOneAssoc($sql,array(':tvcId' => $originalTVCId));
        $now = new \DateTime();
        $originalExpiryDate = \DateTime::createFromFormat('Y-m-d H:i:s.u',$data['expiryDate']);

        if ($originalExpiryDate < $now) {
            return false;
        }
        else{
            return true;
        }

    }

    /**
     * Add requirements to the retrieved key numbers
     */
    public function getRequirementsForKeyNumbers()
    {
        $keyNumbers = $this->list;

        if (empty($keyNumbers)) {
            return;
        }

        foreach ($keyNumbers as $index => $keyNumber) {
            $sql = '             
              SELECT req_station_notes
              FROM 
              dbo.requirements
              JOIN 
              dbo.requirements_tvc on requirements.req_id = requirements_tvc.rtv_req_id
              WHERE rtv_tvc_id = :tvcId  
			  AND datalength(req_station_notes) > 0
            ';

            $data = $this->fetchAllAssoc($sql,array(':tvcId' => $keyNumber['tvcId']));
            $keyNumber['requirements'] = $data;

            $keyNumbers[$index] = $keyNumber;
        }

        $this->list = $keyNumbers;
    }

}
