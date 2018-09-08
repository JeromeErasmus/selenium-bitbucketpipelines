<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Models;
use Elf\Application\Application;
use Elf\Db\AbstractAction;
use Elf\Exception\NotFoundException;
use App\Collections\KeyNumberList;
use App\Models\KeyNumber;
use App\Models\OrderForm;
use Illuminate\Database\Capsule\Manager;
use Illuminate\Support\Facades\DB;

class Job extends AbstractAction {

    //special ones (e.g. used for nesting)
    private $jobId;
    private $agencyId;
    private $advertiserId;
    private $assignedUserId;
    //Eloquent model id's (used for nesting)
    private $additionalContactIds;

    //remaining
    private $referenceNo;
    private $invoiceNumber;
    private $lastAmendedBy;
    private $lastAmendDate;
    private $purchaseOrder;
    private $batchId;
    private $financialPeriod;
    private $numberOfTvcs;
    private $tvcsAssigned;
    private $paymentSightedBy;
    private $paymentSightedAt;
    private $isSyncUpdate;
    private $modifyDate;
    private $createDate;
    private $contactName;
    private $createdBy;
    private $revisionsDetails;
    private $jobTypeId;
    private $jobType;
    private $jobStatusId;
    private $jobStatus;
    private $uiStepId;
    private $tvcFormatId;
    private $offlineEditSupplying;
    private $scriptAlreadySubmitted;
    private $proofOfCharityAlreadyProvided;
    private $complyOp53;
    private $substantiationAlreadyProvided;
    private $altTelephoneNumber;
    private $onAirDate;
    private $requiredByDate;
    private $actionByDate;
    private $paymentMethodId;
    private $paymentMethodName;
    private $paymentMethodCode;
    private $eftBranchBsb;
    private $eftAccountNumber;
    private $eftAccountName;
    private $creditCardId;
    private $referenceNoPrecheckIdLink;
    private $lateFeeAmount;
    private $totalAmount;
    private $readFlag;
    private $readFlagDocument;
    private $readFlagOrder;
    private $altContactName;
    private $owner;
    private $numberOfScripts;
    private $parentId;
    private $commentFlag;
    private $cadAssignedCount;
    private $jobRevisionOriginalJobId;
    private $jobAmendmentOriginalJobId;
    private $submissionDate;
    private $submittedBy;
    private $jobDescription;
    private $jobTitle;
    private $deletedAt;
    private $redHotJob;
    private $jobFinalOrderComment;

    private $showDeleted = false;

    /**
     * @return boolean
     */
    public function isShowDeleted()
    {
        return $this->showDeleted;
    }

    /**
     * @param boolean $showDeleted
     */
    public function setShowDeleted($showDeleted)
    {
        $this->showDeleted = $showDeleted;
    }

    //this maps the input JSON keys to the SQL table keys
    protected $fieldMap = array(
        'jobId' => array(
            'name' => 'job_id',
            'type' => 'numeric',
            'required' => false,
            'allowEmpty' => true
        ),
        'referenceNo' => array(
            'name' => 'job_reference_no',
            'type' => 'string',
            'required' => false,
            'allowEmpty' => true
        ),
        'invoiceNumber' => array(
            'name' => 'job_invoice_number',
            'type' => 'string',
            'required' => false,
            'allowEmpty' => false
        ),
        'lastAmendedBy' => array(
            'name' => 'job_last_amend_by',
            'type' => 'numeric',
            'required' => false,
            'allowEmpty' => false
        ),
        'lastAmendDate' => array(
            'name' => 'job_last_amend_date',
            'type' => 'string',
            'required' => false,
            'allowEmpty' => false
        ),
        'purchaseOrder' => array(
            'name' => 'job_purchase_order',
            'type' => 'string',
            'required' => false,
            'allowEmpty' => true
        ),
        'batchId' => array(
            'name' => 'job_bat_id',
            'type' => 'numeric',
            'required' => false,
            'allowEmpty' => false
        ),
        'financialPeriod' => array(
            'name' => 'job_financial_period',
            'type' => 'string',
            'required' => false,
            'allowEmpty' => false
        ),
        'numberOfTvcs' => array(
            'name' => 'job_number_of_tvcs',
            'type' => 'numeric',
            'required' => false,
            'allowEmpty' => false
        ),
        'tvcsAssigned' => array(
            'name' => 'job_tvcs_assigned',
            'type' => 'numeric',
            'required' => false,
            'allowEmpty' => false
        ),
        'paymentSightedBy' => array(
            'name' => 'job_payment_sighted_by',
            'type' => 'numeric',
            'required' => false,
            'allowEmpty' => false
        ),
        'paymentSightedAt' => array(
            'name' => 'job_payment_sighted_at',
            'type' => 'numeric',
            'required' => false,
            'allowEmpty' => false
        ),
        'isSyncUpdate' => array(
            'name' => 'job_is_sync_update',
            'type' => 'numeric',
            'required' => false,
            'allowEmpty' => false
        ),
        'modifyDate' => array(
            'name' => 'job_modify_date',
            'type' => 'string',
            'required' => false,
            'allowEmpty' => false
        ),
        'createDate' => array(
            'name' => 'job_create_date',
            'type' => 'string',
            'required' => false,
            'allowEmpty' => true,
        ),
        'contactName' => array(
            'name' => 'job_contact_name',
            'type' => 'string',
            'required' => false,
            'allowEmpty' => false
        ),
        'createdBy' => array(
            'name' => 'job_created_by',
            'type' => 'string',
            'required' => true,
            'allowEmpty' => false
        ),
        'revisionsDetails' => array(
            'name' => 'job_revisions_details',
            'type' => 'string',
            'required' => false,
            'allowEmpty' => false
        ),
        'jobTypeId' => array(
            'name' => 'job_jty_id',
            'type' => 'numeric',
            'required' => true,
            'allowEmpty' => false
        ),
        'jobStatusId' => array(
            'name' => 'job_jst_id',
            'type' => 'numeric',
            'required' => false,
            'allowEmpty' => false
        ),
        'uiStepId' => array(
            'name' => 'job_ui_step_id',
            'type' => 'numeric',
            'required' => false,
            'allowEmpty' => false
        ),
        'tvcFormatId' => array(
            'name' => 'job_tfo_id',
            'type' => 'numeric',
            'required' => false,
            'allowEmpty' => false
        ),
        'offlineEditSupplying' => array(
            'name' => 'job_offline_edit_supplying',
            'type' => 'numeric',
            'required' => false,
            'allowEmpty' => false
        ),
        'scriptAlreadySubmitted' => array(
            'name' => 'job_script_already_submitted',
            'type' => 'numeric',
            'required' => false,
            'allowEmpty' => false
        ),
        'proofOfCharityAlreadyProvided' => array(
            'name' => 'job_proof_of_charity_already_provided',
            'type' => 'numeric',
            'required' => false,
            'allowEmpty' => false
        ),
        'complyOp53' => array(
            'name' => 'job_comply_op53',
            'type' => 'numeric',
            'required' => false,
            'allowEmpty' => false
        ),
        'substantiationAlreadyProvided' => array(
            'name' => 'job_substantiation_already_provided',
            'type' => 'numeric',
            'required' => false,
            'allowEmpty' => false
        ),
        'altTelephoneNumber' => array(
            'name' => 'job_alt_telephone_number',
            'type' => 'string',
            'required' => false,
            'allowEmpty' => false
        ),
        'onAirDate' => array(
            'name' => 'job_on_air_date',
            'type' => 'string',
            'required' => true,
            'allowEmpty' => false
        ),
        'requiredByDate' => array(
            'name' => 'job_required_by_date',
            'type' => 'string',
            'required' => true,
            'allowEmpty' => false
        ),
        'actionByDate' => array(
            'name' => 'job_action_by_date',
            'type' => 'string',
            'required' => false,
            'allowEmpty' => false
        ),
        'agencyId' => array(
            'name' => 'job_ag_id',
            'type' => 'numeric',
            'required' => true,
            'allowEmpty' => false
        ),
        'advertiserId' => array(
            'name' => 'job_adv_id',
            'type' => 'numeric',
            'required' => true,
            'allowEmpty' => false
        ),
        'paymentMethodId' => array(
            'name' => 'job_pme_id',
            'type' => 'numeric',
            'required' => false,
            'allowEmpty' => true
        ),
        'eftBranchBsb' => array(
            'name' => 'job_eft_branch_bsb',
            'type' => 'string',
            'required' => false,
            'allowEmpty' => false
        ),
        'eftAccountNumber' => array(
            'name' => 'job_eft_account_number',
            'type' => 'string',
            'required' => false,
            'allowEmpty' => false
        ),
        'eftAccountName' => array(
            'name' => 'job_eft_account_name',
            'type' => 'string',
            'required' => false,
            'allowEmpty' => false
        ),
        'creditCardId' => array(
            'name' => 'job_credit_card_id',
            'type' => 'numeric',
            'required' => false,
            'allowEmpty' => false
        ),
        'referenceNoPrecheckIdLink' => array(
            'name' => 'job_reference_no_precheck_id_link',
            'type' => 'string',
            'required' => false,
            'allowEmpty' => true
        ),
        'lateFeeAmount' => array(
            'name' => 'job_late_fee_amount',
            'type' => 'numeric',
            'required' => false,
            'allowEmpty' => false
        ),
        'totalAmount' => array(
            'name' => 'job_total_amount',
            'type' => 'numeric',
            'required' => false,
            'allowEmpty' => false
        ),
        'readFlag' => array(
            'name' => 'job_read_flag',
            'type' => 'string',
            'required' => false,
            'allowEmpty' => false
        ),
        'readFlagDocument' => array(
            'name' => 'job_read_flag_document',
            'type' => 'numeric',
            'required' => false,
            'allowEmpty' => false
        ),
        'readFlagOrder' => array(
            'name' => 'job_read_flag_order',
            'type' => 'numeric',
            'required' => false,
            'allowEmpty' => false
        ),
        'altContactName' => array(
            'name' => 'job_alt_contact_name',
            'type' => 'string',
            'required' => false,
            'allowEmpty' => false
        ),
//      On the frontend this maps to 'Assigned To'
        'owner' => array(
            'name' => 'job_owner',
            'type' => 'numeric',
            'required' => false,
            'allowEmpty' => false
        ),
//      On the frontend this maps to 'Currently With'
        'assignedUserId' => array(
            'name' => 'job_assigned_user',
            'type' => 'numeric',
            'required' => false,
            'allowEmpty' => false
        ),
        'numberOfScripts' => array(
            'name' => 'job_number_of_scripts',
            'type' => 'numeric',
            'required' => false,
            'allowEmpty' => false
        ),
        'parentId' => array(
            'name' => 'job_parent_id',
            'type' => 'numeric',
            'required' => false,
            'allowEmpty' => false
        ),
        'commentFlag' => array(
            'name' => 'job_comment_flag',
            'type' => 'numeric',
            'required' => false,
            'allowEmpty' => false
        ),
        'submissionDate' => array(
            'name' => 'job_submission_date',
            'type' => 'string',
            'required' => false,
            'allowEmpty' => false
        ),
        'submittedBy' => array(
            'name' => 'job_submitted_by',
            'type' => 'numeric',
            'required' => false,
            'allowEmpty' => false
        ),
        'jobDescription' => array(
            'name' => 'job_description',
            'type' => 'string',
            'required' => false,
            'allowEmpty' => true
        ),
        'jobTitle' => array(
            'name' => 'job_title',
            'type' => 'string',
            'required' => false,
            'allowEmpty' => true
        ),
        'redHotJob' => array(
            'name' => 'job_red_hot',
            'type' => 'numeric',
            'required' => false,
            'allowEmpty' => true
        ),
        'jobFinalOrderComment' => array(
            'name' => 'job_final_order_comment',
            'type' => 'string',
            'required' => false,
            'allowEmpty' => true
        ),
    );

    /**
     *
     * @param type $app
     */
    public function __construct ($app) {
        parent::__construct($app);
        $this->jobId = null;
    }

    /**
     * @param array $data
     * @param array $validationExceptions
     * @return type
     */
    public function validate(Array $data, Array $validationExceptions = array())
    {

        if($this->isPrecheck()) {

            $this->makeOnAirDateOptional();

        }

        return parent::validate($data, $validationExceptions);
    }

    public function isPrecheck()
    {
        $jobTypes = $this->app->config->get('jobTypes');
        return $this->jobTypeId == $jobTypes['precheck'];
    }


    public function makeOnAirDateOptional()
    {

        $this->fieldMap['onAirDate']['required'] = false;

        $this->fieldMap['onAirDate']['allowEmpty'] = true;

    }

    /**
     * This function assumes you have already checked that any element it is controlling is allowed to be deleted
     *
     * The most basic check is that a job has not been submitted
     *
     * @param $data
     * @return bool
     */
    public function deleteJobAndAssocRecords($data){

        $id = $this->getJobId();
        $job = false;

        $params = array(
            'jobId' => $id,
        );

        if(true === $data['tvc']){
            $deleteTvcs= "DELETE FROM tvcs WHERE tvc_job_id = :jobId";
            $tvc = $this->execute($deleteTvcs, $params);
            if(!$tvc){
                // we've failed to execute this, fail and return 500 message so they can try again
                return false;
            }
            $job = true;
            // If provided an array of tvc ids, delete the tvcs
        } else if (!empty($data['tvc'] && is_array($data['tvc'])) ) {
            $deleteTvcs = "DELETE FROM tvcs WHERE tvc_id = :tvcId";

            $tvc = true;

            foreach ($data['tvc'] as $tvcId) {
                $tvc = $this->execute($deleteTvcs, array(
                    'tvcId' => $tvcId
                ));
            }

            if(!$tvc){
                // we've failed to execute this, fail and return 500 message so they can try again
                return false;
            } else {
                return true;
            }
        }
        if(true === $data['documents']){
            $deleteJUMs = "UPDATE job_uploaded_materials SET deleted_at = getdate() WHERE jum_job_id = :jobId";
            $jobUploaded = $this->execute($deleteJUMs, $params);
            if(!$jobUploaded){
                // we've failed to execute this, fail and return 500 message so they can try again
                return false;
            }
            $job = true;
        } else if (!empty($data['documents'] && is_array($data['documents'])) ) {
            $deleteJUMs = "UPDATE job_uploaded_materials SET deleted_at = getdate() WHERE jum_Id = :jumId";

            $jobUploaded = true;

            foreach ($data['documents'] as $documentId) {
                $jobUploaded = $this->execute($deleteJUMs, array(
                    'jumId' => $documentId
                ));
            }

            if(!$jobUploaded){
                // we've failed to execute this, fail and return 500 message so they can try again
                return false;
            } else {
                return true;
            }
        }
        if(true === $data['declarations']){
            $deleteJobDeclarations = "DELETE FROM job_declarations WHERE jde_job_id = :jobId";
            $jobDeclarations = $this->execute($deleteJobDeclarations, $params);
            if(!$jobDeclarations){
                // we've failed to execute this, fail and return 500 message so they can try again
                return false;
            }
            $job = true;
        }
        if(true === $data['comment']){
            $deleteJobComment = "UPDATE comment SET deleted_at = getdate()
                                 WHERE type = (SELECT id from comment_type WHERE name = 'Job') AND ref_id = :jobId";
            $jobComments = $this->execute($deleteJobComment, $params);
            if(!$jobComments){
                // we've failed to execute this, fail and return 500 message so they can try again
                return false;
            }
            $job = true;
        }
        if(true === $data['job']){
            $deleteJob = "UPDATE jobs SET deleted_at = getdate() WHERE job_id = :jobId";
            $job = $this->execute($deleteJob, $params);
        }

        //regardless of if we fail to delete a job or not, return so it can be handled
        return $job;
    }


    /*
     * @dependency Elf\Http\Request $request
     */
    public function load()
    {
        if (empty($this->jobId))
        {
            throw new NotFoundException("Job ID is not set.");
        }

        $sql = "SELECT

        j.job_reference_no,
        j.job_invoice_number,
        j.job_last_amend_by,
        j.job_last_amend_date,
        j.job_purchase_order,
        j.job_bat_id,
        j.job_financial_period,
        j.job_number_of_tvcs,
        j.job_tvcs_assigned,
        j.job_payment_sighted_by,
        j.job_payment_sighted_at,
        j.job_is_sync_update,
        j.job_modify_date,
        j.job_create_date,
        j.job_contact_name,
        j.job_created_by,
        j.job_revisions_details,
        j.job_jty_id,
        jt.jty_name,
        j.job_jst_id,
        js.jst_name,
        j.job_ui_step_id,
        j.job_tfo_id,
        j.job_offline_edit_supplying,
        j.job_script_already_submitted,
        j.job_proof_of_charity_already_provided,
        j.job_comply_op53,
        j.job_substantiation_already_provided,
        j.job_alt_telephone_number,
        j.job_on_air_date,
        j.job_required_by_date,
        j.job_action_by_date,
        j.job_ag_id,
        j.job_adv_id,
        j.job_pme_id,
        j.job_submitted_by,
        pme.pme_name,
        pme.pme_code,
        j.job_eft_branch_bsb,
        j.job_eft_account_number,
        j.job_eft_account_name,
        j.job_credit_card_id,
        j.job_submission_date,
        j.job_reference_no_precheck_id_link,
        j.job_late_fee_amount,
        j.job_total_amount,
        j.job_read_flag,
        j.job_read_flag_document,
        j.job_read_flag_order,
        j.job_alt_contact_name,
        j.job_owner,
        j.job_assigned_user,
        j.job_number_of_scripts,
        j.job_parent_id,
        j.job_description,
        j.job_title,
        j.job_final_order_comment,
        j.job_red_hot,
        j.job_comment_flag


        FROM dbo.jobs AS j

        LEFT JOIN dbo.job_types jt ON j.job_jty_id = jt.jty_id
        LEFT JOIN dbo.job_statuses js ON j.job_jst_id = js.jst_id
        LEFT JOIN dbo.payment_methods pme ON j.job_pme_id = pme.pme_id
        WHERE j.job_id = :job_id
        ";

        if($this->showDeleted === false){
            $sql .= " AND deleted_at IS NULL ";
        }
        else {
            $sql .= " AND deleted_at IS NOT NULL ";
        }

        $data = $this->fetchOneAssoc($sql,array(':job_id' => $this->jobId));

        if ($data == false) {
            throw new NotFoundException("Cannot find job with job id $this->jobId");
        }

        // Retrieve all the additional contact ids

        $params = array(
            ':job_reference_no' => $data['job_reference_no'],
        );

        $sql = "
        SELECT
        c.id
        FROM contact c
        WHERE c.contactable_id = :job_reference_no and c.contactable_type = 'App\\Models\\Job'
        ";

        $this->setAdditionalContactIds($this->fetchAllAssoc($sql,$params));

        $keys = array_keys($data);
        foreach($this->fieldMap as $property => $val) {
            if(in_array($val['name'], $keys)) {
                $this->$property = $data[$val['name']];
            }
        }

        return true;
    }

    /**
     * Determine whether a late fee should be applied based on the required date and turnaround
     * @param $requiredByDate
     * @param string $turnaroundDays
     * @return bool
     */
    public function setPriorityProcessingFeeBasedOnDates($requiredByDate,$turnaroundDays = '-1 Weekdays'){

        $priorityProcessingFee = false;

        $timeOfSubmission = new \DateTime();

        $turnaroundTime = \DateTime::createFromFormat('Y-m-d H:i:s.u', $requiredByDate);
        $turnaroundTime->modify($turnaroundDays);

        // Apply PPF if submitted date is less than today + turnAroundDays
        if ($timeOfSubmission->format('Y-m-d') >= $turnaroundTime->format('Y-m-d')) {
            $priorityProcessingFee = true;
        }

        return $priorityProcessingFee;
    }

    /**
     * Function set the key number late fees
     */
    public function setKeyNumberPriorityProcessingFee(){

        $sql = "SELECT 
                job_required_by_date,
                jty_name 
                FROM jobs 
                JOIN job_types ON job_types.jty_id = jobs.job_jty_id
                WHERE job_id = :jobId";
        $sqlParams = array(
            ':jobId' => $this->jobId,
        );

        $jobArray = $this->fetchOneAssoc($sql, $sqlParams);
        $jobType = $jobArray['jty_name'];

        // Prechecks have no priority fee
        if ($jobType == 'Pre-check') {
            $applyLateFee = false;
        // Infomercial fee application is dependent on charge code turnaround date
        } else if ($jobType == 'Infomercial') {

            $sql = "SELECT charge_codes.cco_turnaround
                     FROM [jobs]
                     JOIN tvcs ON tvcs.tvc_job_id = jobs.job_id
                     JOIN charge_codes ON charge_codes.cco_id = tvcs.tvc_charge_code

                     WHERE job_id = :jobId ";

            $chargeCode = $this->fetchOneAssoc($sql, $sqlParams);
            // The -1 on the day is because of some FreeTV logic present in their terms of application /shrug
            $applyLateFee = $this->setPriorityProcessingFeeBasedOnDates(
                $jobArray['job_required_by_date'],
                '-' . ($chargeCode['cco_turnaround']) . ' days');
        // TVC fee application has a standard two day turnaround date
        } else {
            $applyLateFee = $this->setPriorityProcessingFeeBasedOnDates($jobArray['job_required_by_date']);
        }

        if ($applyLateFee == false) {
            return;
        }

        //otherwise update all key numbers with a late fee flag
        $sql = "UPDATE tvcs SET tvc_late_fee = 1 WHERE tvc_id in (SELECT tvc_id FROM tvcs WHERE tvc_job_id = :jobId)";
        $this->execute($sql, $sqlParams);

    }

    /**
     * Validate if job has dynamic charge codes
     *
     * @return bool
     */
    public function validateJobHasDynamicChargeCodes()
    {
        // TODO. Don't hardcode charge codes
        $sql = "SELECT COUNT(*) as count
                FROM dbo.tvcs t
                INNER JOIN dbo.charge_codes cc ON t.tvc_charge_code = cc.cco_id
                WHERE t.tvc_job_id = :jobId
                AND cc.cco_charge_code IN ('DC1', 'DC2', 'DCR', 'DCR5', 'DCR10')";

        $params = [':jobId' => $this->jobId];
        $query = $this->fetchOneAssoc($sql, $params);

        return $query['count'] && $query['count'] > 0;
    }

    /**
     * Validates if the job's agency and advertiser are approved
     * @param $id
     * @throws NotFoundException
     */
    public function validateAdvertiserAndAgency($id)
    {
        $this->setJobId($id);
        $this->load();
        $jobData = $this->getFullJob();

        if (!empty($jobData['agency']) && !empty($jobData['advertiser'])) {
            if(isset($jobData['agency']['agencyId']) && isset($jobData['advertiser']['advertiserId'])) {
                return;
            }
        }
        throw new \InvalidArgumentException("The agency or the advertiser is not approved");
    }

    /**
     * Validate that the job has one or more key numbers attached
     * @param $jobId
     * @throws \Exception
     */
    public function validateJobHasKeyNumbers($jobId)
    {
        $query = $this->fetchOneAssoc("SELECT COUNT(*) as count FROM dbo.tvcs WHERE tvc_job_id = :jobId", array(
            ':jobId' => $jobId
        ));

        if($query['count'] == 0) {
            throw new \InvalidArgumentException("Job does not have any key numbers attached");
        }
    }

    public function validateJobKeyNumbersHaveValidChargeCodes($jobId){
        $tvcs= $this->fetchAllAssoc("SELECT tvc_charge_code FROM dbo.tvcs WHERE tvc_job_id = :jobId", array(
            ':jobId' => $jobId
        ));

        $now = new \DateTime();

        $chargeCodes = $this->app->model('chargeCode');
        $chargeCodes->setSubmittedDate($now->format('Y-m-d H:i:s'));
        $chargeCodes->setExcludeActiveCheck(true);

        $effectiveChargeCodes = $chargeCodes->getEffectiveChargeCodes();

        $return = false;

        foreach($tvcs as $tvc){
            foreach($effectiveChargeCodes as $effectiveChargeCode){
                $return = false;
                if($effectiveChargeCode['chargeCodeId'] == $tvc['tvc_charge_code']){
                    $return = true;
                    break;
                }
            }
        }

        if($return !== true){
            throw new \Exception("Invalid Charge Code on a Key Number associated with this job");
        }
    }

    /**
     * Checks the job data to confirm that dates are correct
     * @param $jobId
     * @throws NotFoundException
     */
    public function validateJobDataForSubmission($jobId)
    {
        $this->setJobId($jobId);
        $this->load();

        // Check that job data has valid dates set
        $now = new \DateTime('now');
        $now = $now->setTime(0,0,0);
        $data = $this->getAsArray();

        $onAirDate = \DateTime::createFromFormat('Y-m-d H:i:s.u', $data['onAirDate']);
        $requiredByDate = \DateTime::createFromFormat('Y-m-d H:i:s.u', $data['requiredByDate']);

        if(false === $onAirDate && !$this->isPrecheck()) {
            throw new \InvalidArgumentException("The on air date is not set");
        }

        if(false === $requiredByDate) {
            throw new \InvalidArgumentException("The required by date is not set");
        }

        if(!$this->isPrecheck()){
            $onAirDate->setTime(0,0,0);
        }
        $requiredByDate->setTime(0,0,0);

        if($onAirDate < $now && !$this->isPrecheck()) {
            throw new \InvalidArgumentException("The on air date is invalid");
        }
        if($requiredByDate < $now) {
            throw new \InvalidArgumentException("The required by date is invalid");
        }

        if(!empty($data['submissionDate'])) {
            throw new \InvalidArgumentException("Job has already been submitted");
        }
    }

    public function getFullJob()
    {
        $data = $this->getAsArray();

        $nestedModels = [
            'agencyId' => [
                'model' => 'agency',
                'nestedKey' => 'agency'
            ],
            'advertiserId' => [
                'model' => 'advertiser',
                'nestedKey' => 'advertiser'
            ],
            'assignedUserId' => [
                'model' => 'user',
                'nestedKey' => 'currentlyWith'
            ],
            'owner' => [
                'model' => 'user',
                'nestedKey' => 'assignedUser'
            ],
            'jobStatusId' => [
                'model' => 'JobStatus',
                'nestedKey' => 'jobStatus'
            ],
            'jobTypeId' => [
                'model' => 'JobType',
                'nestedKey' => 'jobType'
            ],
            'paymentMethodId' => [
                'model' => 'Payment',
                'nestedKey' => 'paymentMethod'
            ],
//            Not functional at the moment as most jobs don't have a submitted by attached and an exception is thrown when you attempt to retrieve a user from those jobs, potentially useful in the future
//            TODO figure out how to refactor this to retrieve the associated agency user
//            'submittedBy' => [
//                'model' => 'user',
//                'nestedKey' => 'submittedBy'
//            ]
        ];
        $eloquentModels = [
            'additionalContactIds' => [
                'model' => 'Contact',
                'nestedKey' => 'additionalContacts',
            ],
        ];
        foreach($nestedModels as $property => $details) {
            if(!empty($data[$property])) {

                $model = $this->app->model($details['model']);
                $modelData = $model->getById($data[$property]);

                if($details['model'] == 'user' && !empty($modelData)) {
                    unset($modelData['userPermissionSet']);
                }

                $data[$details['nestedKey']] = $modelData;
            }
        }
        $capsule = $this->app->service('eloquent')->getCapsule();
        foreach($eloquentModels as $property => $details) {
            if(!empty($this->$property)) {
                $modelName = "App\\Models\\". $details['model'];
                $model = new $modelName;
                if (!in_array('id',$this->$property)) {
                    //If there are multiple ids that are returned in arrays, this gets all the relevant data for each id
                    //I.e. array (
                    //              [0] => array(
                    //                      [id] => 3
                    //                      [parameter] => 'something'
                    //                     )
                    //              [1] => array(
                    //                      [id] => 17
                    //                      [parameter] => 'something else'
                    //          )
                    foreach($this->$property as $index => $detailsArray) {
                        $response = $model::find($detailsArray['id']);
                        if(!empty($response)) {
                            $data[$details['nestedKey']][] = $response->toRestful();
                        }
                    }
                } elseif (is_array($this->$property)) {
                    //If the id is in an array where the id is immediately there it get's the relevant data this way
                    //i.e. array ( [id] => 7 )
                    //Untested atm
                    $response = $model::find($this->$property['id']);
                    $data[$details['nestedKey']][] = $response->toRestful();
                } else {
                    //If the id is immediately available in the variable, this retrieves the data
                    //i.e. $this->foreignModelKey = 8
                    //Untested atm
                    $response = $model::find($this->$property);
                    $data[$details['nestedKey']][] = $response->toRestful();
                }
            }
        }
        return $data;
    }


    /**
     * Validates that the job has a script submitted with it as well as this is a must have during the submission process
     *
     * @param $jobId
     * @throws NotFoundException
     */
    public function validateJobHasAScript($jobId)
    {
        $documentConstants = $this->app->config->get('documents');
        $documentModel = $this->app->model('Document');
        $documentModel->setJobId($jobId);
        $documentModel->setUploadType($documentConstants['scriptUploadType']);

        try {
            $documents = $documentModel->load();
            return;
        } catch (\Exception $e) {
            throw new NotFoundException('No script has been uploaded for this job');
        }

    }

    public function submitJob($id)
    {
        // If the conditions match, set late fees on the TVC
        $this->setJobId($id);
        $this->load();

        $jobStatusConstants = $this->app->config->get('jobStatuses');


        $requiredByDate = \DateTime::createFromFormat('Y-m-d H:i:s.u',$this->requiredByDate);
        $actionByDate = $requiredByDate->sub(new \DateInterval('P1D'));
        $submittingUser = $this->app->service('user')->getCurrentUser()->getUserSysid();
        $this->setTvcLateFees();

        $sql = "UPDATE dbo.jobs
                  SET job_submission_date = getdate(),
                      job_action_by_date = '{$actionByDate->format('Y-m-d H:i:s')}',
                      job_submitted_by = {$submittingUser},
                      job_jst_id = {$jobStatusConstants['readyForReview']}

                  WHERE job_id = :jobId";

        $this->updateContentCodeInKeyNumbersFromJobId($id);
        $this->copyOp48ToKeyNumbersFromJobId($id);
        $this->copyCTimeToKeyNumbersFromJobId($id);

        return $this->update($sql, array(':jobId' => $id));
    }

    /**
     * @param $id
     * @return mixed
     * @throws \Exception
     */
    public function updateContentCodeInKeyNumbersFromJobId($id)
    {

        if(empty($id) || !is_numeric($id)) {
            throw new \Exception("Invalid Argument: Provided job reference must be numeric");
        }

        $sql = "DECLARE @jobId int
                SET @jobId = :job_id;
                UPDATE dbo.tvcs
                  SET tvc_content_code = (
                    SELECT
                        CASE
                            WHEN jde_au_nz_produced = 1 THEN 'A'
                            WHEN jde_au_nz_exempt = 1 THEN 'E'
                            ELSE 'F'
                        END as content
                    FROM dbo.job_declarations
                    WHERE jde_job_id = @jobId)
                WHERE tvc_job_id = @jobId  ";

        $params = [
            ':job_id' => $id,
        ];

        return $this->update($sql, $params);
    }

    /**
     * @param $id
     * @return mixed
     * @throws \Exception
     */
    public function copyOp48ToKeyNumbersFromJobId($id)
    {

        if(empty($id) || !is_numeric($id)) {
            throw new \Exception("Invalid Argument: Provided job reference must be numeric");
        }

        $sql = "DECLARE @jobId int
                SET @jobId = :job_id;
                UPDATE dbo.tvcs
                  SET tvc_op48 = (
                    SELECT TOP 1 jde_meets_audio_levels_and_loudness
                    FROM dbo.job_declarations
                    WHERE jde_job_id = @jobId)
                WHERE tvc_job_id = @jobId  ";

        $params = [
            ':job_id' => $id,
        ];

        return $this->update($sql, $params);
    }

    public function copyCTimeToKeyNumbersFromJobId($id)
    {

        if(empty($id) || !is_numeric($id)) {
            throw new \Exception("Invalid Argument: Provided job reference must be numeric");
        }

        $sql = "DECLARE @jobId int
                SET @jobId = :job_id;
                UPDATE dbo.tvcs
                  SET tvc_ctime = (
                    SELECT TOP 1 jde_scheduled_in_children_programs
                    FROM dbo.job_declarations
                    WHERE jde_job_id = @jobId)
                WHERE tvc_job_id = @jobId  ";

        $params = [
            ':job_id' => $id,
        ];

        return $this->update($sql, $params);
    }

    public function save()
    {
        if (empty($this->jobId)) {
            return $this->createRecord();
        } else {
            return $this->updateRecord();
        }
    }

    public function createRecord()
    {
        $params = array();
        $columns = "";
        $values = "";

        $fieldmap = $this->fieldMap;
        unset($fieldmap['jobId']);
        unset($fieldmap['referenceNo']);
        unset($fieldmap['createDate']);
        foreach ($fieldmap as $property => $details) {
            $columns .= "{$details['name']},";
            $values .= ":$property,";
            $params[":$property"] = $this->$property;
        }

        $columns = rtrim($columns,',');
        $values = rtrim($values,',');

        $sql = "INSERT INTO dbo.jobs($columns,job_create_date) VALUES($values, getdate())";

        $id = $this->insert($sql, $params);

        if ($id !== false) {
            $this->setJobId($id);
            return true;
        } else {
            throw new \Exception("Couldn't insert new job.");
            return false;
        }
    }

    public function updateRecord()
    {
        $params = array();
        $columns = "";

        $fieldmap = $this->fieldMap;
        unset($fieldmap['jobId']);
        unset($fieldmap['referenceNo']);

        foreach ($fieldmap as $property => $details) {
            $columns .= "{$details['name']} = :$property,";
            $params[":$property"] = $this->$property;
        }
        $columns = rtrim($columns,',');


        $sql = "UPDATE dbo.jobs SET $columns WHERE job_id = :job_id";
        $params[':job_id'] = $this->jobId;
        return $this->update($sql, $params);
    }

    public function setTvcLateFees() {
//        Conditions for setting late fees //
        $dueDate = \DateTime::createFromFormat("Y-m-d H:i:s.u",$this->requiredByDate)->format('d-m-Y');
        $submissionDate = new \DateTime('now');
        $submissionDate->format('d-m-Y');

        if($dueDate == $submissionDate) {

            $keyNumbers = $this->getAllKeyNumbers();

            $tvcIds = array();
            foreach($keyNumbers as $index => $keyNumber) {
                $tvcIds[] = $keyNumber['tvcId'];
            }
            if (empty($tvcIds)) {
                return;
            }
            $inputData = array( 'lateFee' => 1);

            foreach ($tvcIds as $index => $tvcId) {
                $tvcModel = $this->app->model('keynumber');
                $tvcModel->setTvcId($tvcId);
                $tvcModel->load();

                $tvcModel->setFromArray($inputData);
                $tvcModel->save();
            }
        }
        return;
    }

    public function getAllKeyNumbers()
    {
        $params = array('jobId' => $this->jobId);
        $keyNumberList = $this->app->collection('keynumberlist');
        $keyNumberList->setParams($params);
        $keyNumbers = $keyNumberList->getAll();

        // Retrieve charge codes for each Key Number

        $chargeCode = $this->app->model('chargeCode');
        $submittedDate = new \DateTime('now');
        $chargeCode->setSubmittedDate($submittedDate->format('Y-m-d H:i:s'));
        $effectiveChargeCodes = $chargeCode->getEffectiveChargeCodes();

        $capsule = $this->app->service('eloquent')->getCapsule();
        foreach ($keyNumbers as $index => $keyNumber) {
            $chargeCodeId = $keyNumbers[$index]['chargeCode'];
            $associatedChargeCode = array_filter($effectiveChargeCodes, function($chargeCode) use ($chargeCodeId) {
                if($chargeCode['chargeCodeId'] == $chargeCodeId) {
                    return $chargeCode;
                }
            });

            if(!empty($associatedChargeCode)) {
                $keyNumbers[$index]['chargeCode'] = $associatedChargeCode[0];
            }
        }

        return $keyNumbers;
    }

    public function submitInitialOrderForm($jobId)
    {
        $this->setJobId($jobId);
        $this->load();
        $jobChecklistModel = $this->app->model('JobChecklist');
        $orderForm = $jobChecklistModel->compileFinalOrderFormData($jobId);

        $orderFormBlob = json_encode($orderForm);

        $inputArray = array(
            'type' => 1,
            'jobId' => $this->getJobId(),
            'createdBy' => $this->app->service('user')->getCurrentUser()->getUserSysid(),
            'inputData' => $orderFormBlob
        );

        $capsule = $this->app->service('eloquent')->getCapsule();
        $model = new OrderForm();
        $model->setFromArray($inputArray);

        if($model->validate()) {
            $model->save();
        }

        // Create the initial order form document
        $documentService = $this->app->service('pdfDocumentGeneration');
        $documentService->setFileName('Original_Order_Form-' . $this->getJobId());
        $documentThings = $documentService->generateDocument('originalOrderForm',$orderForm);

    }

    public function retrieveAmendedCount($originalJobId)
    {
        $query = $this->fetchOneAssoc("SELECT COUNT(*) as amendmentCount FROM dbo.jobs WHERE job_amendment_original_job_id = :originalJobId AND job_amendment = 1", array(
            ':originalJobId' => $originalJobId
        ));
        return $query['amendmentCount'] + 1;
    }

    public function deleteJob($id)
    {
        return false;
    }

    public function recordExists($id)
    {
        $query = $this->fetchOneAssoc("SELECT COUNT(job_id) as id_exists FROM dbo.jobs WHERE job_id = :refNo", array(
            ':refNo' => $id
        ));
        if ($query['id_exists'] != "1")
            return false;       //the id doesn't exist
        else
            return true;
    }

    public function getAsArray()
    {
        $data = array();

        foreach($this->fieldMap as $property => $details) {
            if (property_exists($this, $property)) {
                $data[$property] = $this->$property;
            }
        }
        return $data;
    }

    public function setFromArray($data, $patch = false)
    {
        foreach($this->fieldMap as $key => $mapping) { //loop through the field map and only pick up data that we want
            $setMethod = "set".$key;
            if(method_exists($this, $setMethod) && isset($data[$key])) { // check if we can actually update this field
                $this->$setMethod($data[$key],$patch);
            }
        }
    }

    /**
     * Returns the jobId if the job is editable by this user
     *
     * This is intended to be used for changing the job status for resubmission from OAS
     *      and as such only select jobs that are in AgencyFeedback status
     *
     * @param $jobId
     * @param $userId
     * @return bool
     * @throws NotFoundException
     */
    public function canUserModify($jobId, $userId){
        $sql = "
            SELECT
              j.job_id,
              j.job_owner,
              j.job_assigned_user
            FROM
              jobs j
              JOIN job_statuses js
                ON js.jst_id = j.job_jst_id
            WHERE j.job_id = :jobId
              AND (
                j.job_created_by = :userId
                OR j.job_ag_id =
                (SELECT
                  agu.agu_ag_id
                FROM
                  agency_users agu
                WHERE agu.agu_id = :agUserId)
              ) 
        ";

        $params = array(
            ':jobId' => $jobId,
            ':userId' => $userId,
            ':agUserId' => $userId,
        );

        $data = $this->fetchOneAssoc($sql,$params);
        return $data;
    }


    /**
     * Change the job status of the specified job to the specified job status else sets it to ready for review
     *
     * @param $jobId
     * @param int $jobStatus
     * @return bool
     */
    public function updateJobStatus($jobId, $jobStatus = 1)
    {
        $sql = "
            UPDATE jobs 
              SET job_jst_id = :jobStatus
            WHERE job_id = :jobId
            ;
        ";

        $params = array(
            ':jobStatus' => $jobStatus,
            ':jobId' => $jobId,
        );

        $jobStatusUpdated = $this->execute($sql, $params);

        if(!$jobStatusUpdated){
            return false;
        }
        else {
            return true;
        }
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


    public function getJobId()
    {
        return $this->jobId;
    }

    public function setJobId($id)
    {
        $this->jobId = $id;
    }

    /**
     * @param mixed $agencyId
     */
    public function setAgencyId($agencyId)
    {
        $this->agencyId = $agencyId;
    }

    /**
     * @param mixed $advertiserId
     */
    public function setAdvertiserId($advertiserId)
    {
        $this->advertiserId = $advertiserId;
    }

    /**
     * @param mixed $assignedUserId
     */
    public function setAssignedUserId($assignedUserId)
    {
        $this->assignedUserId = $assignedUserId;
    }

    /**
     * @param mixed $referenceNo
     */
    public function setReferenceNo($referenceNo)
    {
        $this->referenceNo = $referenceNo;
    }

    /**
     * @param mixed $invoiceNumber
     */
    public function setInvoiceNumber($invoiceNumber)
    {
        $this->invoiceNumber = $invoiceNumber;
    }

    /**
     * Only set these if the job is being edited
     * @param mixed $lastAmendedBy
     * @param bool $patch
     */
    public function setLastAmendedBy($lastAmendedBy,$patch = false)
    {
        if ($patch == true) {
            $this->lastAmendedBy = $lastAmendedBy;
        }
    }

    /**
     * Only set these if the job is being edited
     * @param mixed $lastAmendDate
     * @param bool $patch
     */
    public function setLastAmendDate($lastAmendDate,$patch = false)
    {
        if ($patch == true) {
            $now = new \DateTime('now');
            $this->lastAmendDate = $now->format('Y-m-d H:i:s');
        }
    }

    /**
     * @param mixed $purchaseOrder
     */
    public function setPurchaseOrder($purchaseOrder)
    {
        $this->purchaseOrder = $purchaseOrder;
    }

    /**
     * @param mixed $batchId
     */
    public function setBatchId($batchId)
    {
        $this->batchId = $batchId;
    }

    /**
     * @param mixed $financialPeriod
     */
    public function setFinancialPeriod($financialPeriod)
    {
        $this->financialPeriod = $financialPeriod;
    }

    /**
     * @param mixed $numberOfTvcs
     */
    public function setNumberOfTvcs($numberOfTvcs)
    {
        $this->numberOfTvcs = $numberOfTvcs;
    }

    /**
     * @param mixed $tvcsAssigned
     */
    public function setTvcsAssigned($tvcsAssigned)
    {
        $this->tvcsAssigned = $tvcsAssigned;
    }

    /**
     * @param mixed $paymentSightedBy
     */
    public function setPaymentSightedBy($paymentSightedBy)
    {
        $this->paymentSightedBy = $paymentSightedBy;
    }

    /**
     * @param mixed $paymentSightedAt
     */
    public function setPaymentSightedAt($paymentSightedAt)
    {
        $this->paymentSightedAt = $paymentSightedAt;
    }

    /**
     * @param mixed $isSyncUpdate
     */
    public function setIsSyncUpdate($isSyncUpdate)
    {
        $this->isSyncUpdate = $isSyncUpdate;
    }

    /**
     * @param mixed $modifyDate
     */
    public function setModifyDate($modifyDate)
    {
        $this->modifyDate = $modifyDate;
    }

    /**
     * @param mixed $createDate
     */
    public function setCreateDate($createDate)
    {
        $this->createDate = $createDate;
    }

    /**
     * @param mixed $contactName
     */
    public function setContactName($contactName)
    {
        $this->contactName = $contactName;
    }

    /**
     * @param mixed $createdBy
     */
    public function setCreatedBy($createdBy)
    {
        $this->createdBy = $createdBy;
    }

    /**
     * @param mixed $revisionsDetails
     */
    public function setRevisionsDetails($revisionsDetails)
    {
        $this->revisionsDetails = $revisionsDetails;
    }

    /**
     * @param mixed $jobTypeId
     */
    public function setJobTypeId($jobTypeId)
    {
        $this->jobTypeId = $jobTypeId;
    }

    /**
     * @param mixed $jobType
     */
    public function setJobType($jobType)
    {
        $this->jobType = $jobType;
    }

    /**
     * @param mixed $jobStatusId
     * @param bool $patch
     */
    public function setJobStatusId($jobStatusId,$patch = false)
    {
        if ($patch == true) {
            $this->jobStatusId = $jobStatusId;
        }
    }

    /**
     * @param mixed $jobStatus
     */
    public function setJobStatus($jobStatus)
    {
        $this->jobStatus = $jobStatus;
    }

    /**
     * @param mixed $uiStepId
     */
    public function setUiStepId($uiStepId)
    {
        $this->uiStepId = $uiStepId;
    }

    /**
     * @param mixed $TvcFormatId
     */
    public function setTvcFormatId($TvcFormatId)
    {
        $this->TvcFormatId = $TvcFormatId;
    }

    /**
     * @param mixed $offlineEditSupplying
     */
    public function setOfflineEditSupplying($offlineEditSupplying)
    {
        $this->offlineEditSupplying = $offlineEditSupplying;
    }

    /**
     * @param mixed $scriptAlreadySubmitted
     */
    public function setScriptAlreadySubmitted($scriptAlreadySubmitted)
    {
        $this->scriptAlreadySubmitted = $scriptAlreadySubmitted;
    }

    /**
     * @param mixed $proofOfCharityAlreadyProvided
     */
    public function setProofOfCharityAlreadyProvided($proofOfCharityAlreadyProvided)
    {
        $this->proofOfCharityAlreadyProvided = $proofOfCharityAlreadyProvided;
    }

    /**
     * @param mixed $complyOp53
     */
    public function setComplyOp53($complyOp53)
    {
        $this->complyOp53 = $complyOp53;
    }

    /**
     * @param mixed $substantiationAlreadyProvided
     */
    public function setSubstantiationAlreadyProvided($substantiationAlreadyProvided)
    {
        $this->substantiationAlreadyProvided = $substantiationAlreadyProvided;
    }

    /**
     * @param mixed $altTelephoneNumber
     */
    public function setAltTelephoneNumber($altTelephoneNumber)
    {
        $this->altTelephoneNumber = $altTelephoneNumber;
    }

    /**
     * @param mixed $onAirDate
     */
    public function setOnAirDate($onAirDate)
    {
        $this->onAirDate = $onAirDate;
    }

    /**
     * @param mixed $requiredByDate
     */
    public function setRequiredByDate($requiredByDate)
    {
        $this->requiredByDate = $requiredByDate;
    }

    /**
     * Can only be set when patching
     * @param mixed $actionByDate
     * @param bool $patch
     */
    public function setActionByDate($actionByDate, $patch)
    {
        if ($patch == true) {
            $this->actionByDate = $actionByDate;
        }
    }

    /**
     * @param mixed $paymentMethodId
     */
    public function setPaymentMethodId($paymentMethodId)
    {
        $this->paymentMethodId = $paymentMethodId;
    }

    /**
     * @param mixed $paymentMethodName
     */
    public function setPaymentMethodName($paymentMethodName)
    {
        $this->paymentMethodName = $paymentMethodName;
    }

    /**
     * @param mixed $paymentMethodCode
     */
    public function setPaymentMethodCode($paymentMethodCode)
    {
        $this->paymentMethodCode = $paymentMethodCode;
    }

    /**
     * @param mixed $eftBranchBsb
     */
    public function setEftBranchBsb($eftBranchBsb)
    {
        $this->eftBranchBsb = $eftBranchBsb;
    }

    /**
     * @param mixed $eftAccountNumber
     */
    public function setEftAccountNumber($eftAccountNumber)
    {
        $this->eftAccountNumber = $eftAccountNumber;
    }

    /**
     * @param mixed $eftAccountName
     */
    public function setEftAccountName($eftAccountName)
    {
        $this->eftAccountName = $eftAccountName;
    }

    /**
     * @param mixed $creditCardId
     */
    public function setCreditCardId($creditCardId)
    {
        $this->creditCardId = $creditCardId;
    }

    /**
     * @param mixed $referenceNoPrecheckIdLink
     */
    public function setReferenceNoPrecheckIdLink($referenceNoPrecheckIdLink)
    {
        $this->referenceNoPrecheckIdLink = $referenceNoPrecheckIdLink;
    }

    /**
     * @param mixed $lateFeeAmount
     */
    public function setLateFeeAmount($lateFeeAmount)
    {
        $this->lateFeeAmount = $lateFeeAmount;
    }

    /**
     * @param mixed $totalAmount
     */
    public function setTotalAmount($totalAmount)
    {
        $this->totalAmount = $totalAmount;
    }

    /**
     * @param mixed $readFlag
     */
    public function setReadFlag($readFlag)
    {
        $this->readFlag = $readFlag;
    }

    /**
     * @param mixed $readFlagDocument
     */
    public function setReadFlagDocument($readFlagDocument)
    {
        $this->readFlagDocument = $readFlagDocument;
    }

    /**
     * @param mixed $readFlagOrder
     */
    public function setReadFlagOrder($readFlagOrder)
    {
        $this->readFlagOrder = $readFlagOrder;
    }

    /**
     * @param mixed $altContactName
     */
    public function setAltContactName($altContactName)
    {
        $this->altContactName = $altContactName;
    }

    /**
     * @param mixed $owner
     */
    public function setOwner($owner)
    {
        $this->owner = $owner;
    }

    /**
     * @param mixed $numberOfScripts
     */
    public function setNumberOfScripts($numberOfScripts)
    {
        $this->numberOfScripts = $numberOfScripts;
    }

    /**
     * @param mixed $parentId
     */
    public function setParentId($parentId)
    {
        $this->parentId = $parentId;
    }

    /**
     * @param mixed $commentFlag
     */
    public function setCommentFlag($commentFlag)
    {
        $this->commentFlag = $commentFlag;
    }

    /**
     * @param mixed $cadAssignedCount
     */
    public function setCadAssignedCount($cadAssignedCount)
    {
        $this->cadAssignedCount = $cadAssignedCount;
    }

    /**
     * @return mixed
     */
    public function getAdditionalContactIds()
    {
        return $this->additionalContactIds;
    }

    /**
     * @param mixed $additionalContactIds
     */
    public function setAdditionalContactIds($additionalContactIds)
    {
        $this->additionalContactIds = $additionalContactIds;
    }

    /**
     * @return mixed
     */
    public function getRequiredByDate()
    {
        return $this->requiredByDate;
    }

    /**
     * @return mixed
     */
    public function getAgencyId()
    {
        return $this->agencyId;
    }

    /**
     * @return integer
     */
    public function getAdvertiserId()
    {
        return $this->advertiserId;
    }

    /**
     * @return mixed
     */
    public function getCreatedBy()
    {
        return $this->createdBy;
    }


    /**
     * @return mixed
     */
    public function getJobRevisionOriginalJobId()
    {
        return $this->jobRevisionOriginalJobId;
    }

    /**
     * @param mixed $jobRevisionOriginalJobId
     */
    public function setJobRevisionOriginalJobId($jobRevisionOriginalJobId)
    {
        $this->jobRevisionOriginalJobId = $jobRevisionOriginalJobId;
    }

    /**
     * @return mixed
     */
    public function getJobDescription()
    {
        return $this->jobDescription;
    }

    /**
     * @param mixed $jobDescription
     */
    public function setJobDescription($jobDescription)
    {
        $this->jobDescription = $jobDescription;
    }

    /**
     * @return mixed
     */
    public function getJobTitle()
    {
        return $this->jobTitle;
    }

    /**
     * @param mixed $jobTitle
     */
    public function setJobTitle($jobTitle)
    {
        $this->jobTitle = $jobTitle;
    }

    /**
     * @return mixed
     */
    public function getJobFinalOrderComment()
    {
        return $this->jobFinalOrderComment;
    }

    /**
     * @param mixed $jobFinalOrderComment
     */
    public function setJobFinalOrderComment($jobFinalOrderComment)
    {
        $this->jobFinalOrderComment = $jobFinalOrderComment;
    }



    /**
     * @return mixed
     */
    public function getRedHotJob()
    {
        return $this->redHotJob;
    }

    /**
     * @param mixed $redHotJob
     */
    public function setRedHotJob($redHotJob)
    {
        if (empty($redHotJob)) {
            $this->redHotJob = 0;
            return;
        }
        $this->redHotJob = 1;
    }

    /**
     * Check for red hot job status
     * @return bool
     */
    public function isRedHotJob()
    {
        if (empty($this->redHotJob)) {
            return false;
        }
        return true;

    }

    /**
     * @return mixed
     */
    public function getSubmissionDate()
    {
        return $this->submissionDate;
    }

    /**
     * @param mixed $submissionDate
     */
    public function setSubmissionDate($submissionDate)
    {
        $this->submissionDate = $submissionDate;
    }



}
