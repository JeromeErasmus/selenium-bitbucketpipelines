<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Models;

use Elf\Core\Module;


class MetaJob extends Module {
    //put your code here
    public $jobFields = array();
    
    public function addData($data)
    {
        $this->jobFields = $data;
    }
 
    public function validate()
    {
        $inputData = $this->jobFields;
        
        if (empty($inputData)) {
            throw new \Exception("No input data");
        }

        foreach($this->fieldsMap as $key=>$value) {
            
            if (!array_key_exists($key, $inputData)) {         //check if all the fields defined in $fieldsMap (for now) exist in the input data
                throw new \Exception("$key doesn't exist!");
            }

            //now check if all the input data fields are the correct data type
            if (gettype($inputData[$key]) != 'NULL' && gettype($inputData[$key]) != $this->fieldsMap[$key]['type']) {
                throw new \Exception("$key doesn't match the data type.");
            }
        }

        return true;
        
    }
    
    public function persistJob()
    {
        $job = $this->app->model('Job');
        $id = $job->createJob($this->jobFields);
        return $id;
    }
    
    //this maps the input JSON keys to the SQL table keys
    private $fieldsMap = array(
             'referenceNo' => array(
                'name' => 'job_reference_no',
                'type' => 'string'
                ),
             'amendment' => array(
                 'name' => 'job_amendment',
                 'type' => 'string'
                 ),
             'invoiceNumber' => array(
                 'name' => 'job_invoice_number',
                 'type' => 'string'
                 ),
             'lastAmendedBy' => array(
                 'name' => 'job_last_amend_by',
                 'type' => 'integer'
                 ),
             'lastAmendDate' => array(
                 'name' => 'job_last_amend_date',
                 'type' => 'string'
                 ),
             'purchaseOrder' => array(
                 'name' => 'job_purchase_order',
                 'type' => 'string'
                 ),
             'batId' => array(
                 'name' => 'job_bat_id',
                 'type' => 'integer'
                 ),
             'financialPeriod' => array(
                 'name' => 'job_financial_period',
                 'type' => 'string'
                 ),
             'numberOfTvcs' => array(
                 'name' => 'job_number_of_tvcs',
                 'type' => 'integer'
                 ),
             'tvcsAssigned' => array(
                 'name' => 'job_tvcs_assigned',
                 'type' => 'integer'
                 ),
             'paymentSightedBy' => array(
                 'name' => 'job_payment_sighted_by',
                 'type' => 'integer'
                 ),
             'paymentSightedAt' => array(
                 'name' => 'job_payment_sighted_at',
                 'type' => 'integer'
                 ), 
             'isSyncUpdate' => array(
                 'name' => 'job_is_sync_update',
                 'type' => 'integer'
                 ),
             'contactName' => array(
                 'name' => 'job_contact_name',
                 'type' => 'string'
                 ),
             'createdBy' => array(
                 'name' => 'job_created_by',
                 'type' => 'integer'
                 ),
             'revisionsDetails' => array(
                 'name' => 'job_revisions_details',
                 'type' => 'string'
                 ),
             'jtyId' => array(
                 'name' => 'job_jty_id',
                 'type' => 'integer'
                 ),
             'uiStepId' => array(
                 'name' => 'job_ui_step_id',
                 'type' => 'integer'
                 ),
             'tfoId' => array(
                 'name' => 'job_tfo_id',
                 'type' => 'integer'
                 ),
             'offlineEditSupplying' => array(
                 'name' => 'job_offline_edit_supplying',
                 'type' => 'integer'
                 ),
             'scriptAlreadySubmitted' => array(
                 'name' => 'job_script_already_submitted',
                 'type' => 'integer'
                 ),
             'proofOfCharityAlreadyProvided' => array(
                 'name' => 'job_proof_of_charity_already_provided',
                 'type' => 'integer'
                 ),
             'complyOp53' => array(
                 'name' => 'job_comply_op53',
                 'type' => 'integer'
                 ),
             'substantiationAlreadyProvided' => array(
                 'name' => 'job_substantiation_already_provided',
                 'type' => 'integer'
                 ),
             'altTelephoneNumber' => array(
                 'name' => 'job_alt_telephone_number',
                 'type' => 'string'
                 ),
             'onAirDate' => array(
                 'name' => 'job_on_air_date',
                 'type' => 'string'
                 ),
             'requiredByDate' => array(
                 'name' => 'job_required_by_date',
                 'type' => 'string'
                 ),
             'actionByDate' => array(
                 'name' => 'job_action_by_date',
                 'type' => 'string'
                 ),
             'agId' => array(
                 'name' => 'job_ag_id',
                 'type' => 'integer'
                 ),
             'advId' => array(
                 'name' => 'job_adv_id',
                 'type' => 'integer'
                 ),
             'pmeId' => array(
                 'name' => 'job_pme_id',
                 'type' => 'integer'
                 ),
             'eftBranchBsb' => array(
                 'name' => 'job_eft_branch_bsb',
                 'type' => 'string'
                 ),
             'eftAccountNumber' => array(
                 'name' => 'job_eft_account_number',
                 'type' => 'string'
                 ),
             'eftAccountName' => array(
                 'name' => 'job_eft_account_name',
                 'type' => 'string'
                 ),
             'creditCardId' => array(
                 'name' => 'job_credit_card_id',
                 'type' => 'integer'
                 ),
             'jstId' => array(
                 'name' => 'job_jst_id',
                 'type' => 'integer'
                 ),
             'submissionDate' => array(
                 'name' => 'job_submission_date',
                 'type' => 'string'
                 ),
             'referenceNoPrecheckIdLink' => array(
                 'name' => 'job_reference_no_precheck_id_link',
                 'type' => 'integer'
                 ),
             'lateFee_amount' => array(
                 'name' => 'job_late_fee_amount',
                 'type' => 'integer'
                 ),
             'totalAmount' => array(
                 'name' => 'job_total_amount',
                 'type' => 'integer'
                 ),
             'readFlag' => array(
                 'name' => 'job_read_flag',
                 'type' => 'string'
                 ),
             'readFlagDocument' => array(
                 'name' => 'job_read_flag_document',
                 'type' => 'integer'
                 ),
             'readFlagOrder' => array(
                 'name' => 'job_read_flag_order',
                 'type' => 'integer'
                 ),
             'altContactName' => array(
                 'name' => 'job_alt_contact_name',
                 'type' => 'string'
                 ),
             'owner' => array(
                 'name' => 'job_owner',
                 'type' => 'integer'
                 ),
             'assignedUser' => array(
                 'name' => 'job_assigned_user',
                 'type' => 'integer'
                 ),
             'numberOfScripts' => array(
                 'name' => 'job_number_of_scripts',
                 'type' => 'integer'
                 ),
             'parentId' => array(
                 'name' => 'job_parent_id',
                 'type' => 'integer'
             ),
             'commentFlag' => array(
                 'name' => 'job_comment_flag',
                 'type' => 'integer'
             )
        );
}
