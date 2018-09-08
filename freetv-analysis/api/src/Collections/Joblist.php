<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Collections;

use Elf\Utility\Convert;

class Joblist extends \Elf\Db\AbstractCollection {

    public $list;
    private $preCheckId;
    private $sqlParams;
    private $filter;
    private $leftJoin;
    private $innerJoin;
    private $numberOfResults = 100;
    private $filterList;
    private $clientId;
    private $order;


    private function applyMapping($mappingArray, $fieldMapping, $checkMapping = false){
        if($checkMapping === true && array_key_exists($fieldMapping, $mappingArray )){
            return true;
        }

        if(array_key_exists($fieldMapping, $mappingArray)){
            return $mappingArray[$fieldMapping]['name'];
        }
        return false;
    }

    /**
     * @return mixed
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * @param mixed $order
     */
    public function setOrder($order)
    {
        $this->order = $order;
    }

    /**
     * @return mixed
     */
    public function getClientId()
    {
        return $this->clientId;
    }

    /**
     * @param mixed $clientId
     */
    public function setClientId($clientId)
    {
        $this->clientId = $clientId;
    }

    private $fieldMap = array(
        'jobId' => array(
            'name' => 'job_id',
        ),
        'jobDescription' => array(
            'name' => 'job_description',
        ),
        'jobTitle' => array(
            'name' => 'job_title',
        ),
        'referenceNo' => array(
            'name' => 'job_reference_no',
        ),
        'advertiserId' => array(
            'name' => 'job_adv_id',
        ),
        'advertiserName' => array(
            'name' => 'adv_name',
        ),
        'agencyBillingCode' => array(
            'name' => 'ag_billing_code',
        ),
        'agencyCode' => array(
            'name' => 'ag_code',
        ),
        'jobStatus' => array(
            'name' => 'jst_name',
        ),
        'jobStatusId' => array(
            'name' => 'job_jst_id',
        ),
        'tvcCount' => array(
            'name' => 'num_tvc',
        ),
        'jobType' => array(
            'name' => 'jty_name',
        ),
        'jobTypeId' => array(
            'name' => 'jty_id',
        ),
        'jobReadFlag' => array(
            'name' => 'job_read_flag',
        ),
        'assignedUsername' => array(
            'name' => 'assigned_username',
        ),
        'actionByDate' => array(
            'name' => 'job_action_by_date',
        ),
        'agencyStopCreditId' => array(
            'name' => 'ag_scr_id',
        ),
        'stopCreditReason' => array(
            'name' => 'scr_reason'
        ),
        'submissionDate' => array(
            'name' => 'job_submission_date',
        ),
        'parentId' => array(
            'name' => 'job_parent_id',
        ),
        'agencyIsApproved' => array(
            'name' => 'ag_is_approved',
            'type' => 'boolean'
        ),
        'advertiserIsApproved' => array(
            'name' => 'adv_is_approved',
            'type' => 'boolean'
        ),
        'tvcChargeCode' => array(
            'name' => 'tvc_charge_code',
        ),
        'cadNumber' => array(
            'name' => 'cad_no'
        ),
        'revisionDetails' => array(
            'name' => 'job_revisions_details'
        ),
        'submittedBy' => array(
            'name' => 'job_submitted_by'
        ),
        'revisedJobId' => array(
            'name' => 'revisedJobId'
        ),
        'redHotJob' => array(
            'name' => 'job_red_hot'
        ),
        'tvcLateFee' => array(
            'name' => 'tvc_late_fee'
        ),
    );

    public function __construct($app) {
        parent::__construct($app);
        $this->sqlParams = array();
        $this->filter = '';
        $jobTypes = $this->app->config->get('jobTypes');
        $this->preCheckId = $jobTypes['precheck'];
        // $this->filterList = $this->app->collection('jobFilterList');
    }

    // set the params for filtering
    public function setParams($params = array()) {
        //if change '1=1' here make sure to change it at the end of the method as well
        //set parameters coming form the filter selection
        $this->filter = "1=1 ";
        $this->leftJoin = '';

        $closedStatus = $this->app->config->config['jobStatuses']['closed'];
        $cadAssignedStatus = $this->app->config->config['jobStatuses']['cadAssigned'];
        $agencyFeedbackStatus = $this->app->config->config['jobStatuses']['agencyFeedback'];

        /*
         * true: returns only jobs that have been deleted
         * false: returns only jobs that have not been deleted
         * NULL: returns all jobs
         */
        if(isset($params['deleted']) && $params['deleted'] == 1){
            $this->filter .= " AND j.deleted_at IS NOT NULL ";
        }
        elseif(isset($params['deleted']) && $params['deleted'] == 0 ){
            $this->filter .= " AND j.deleted_at IS NULL ";
        }

        if(isset($params['sortBy'])){

            if (isset($params['orderBy']) && $params['orderBy'] == 'desc' && $this->applyMapping($this->fieldMap,$params['sortBy'], true)){
                $this->order = 'ORDER BY '.$this->applyMapping($this->fieldMap,$params['sortBy']).' DESC';
            }else {
                $this->order = 'ORDER BY '.$this->applyMapping($this->fieldMap,$params['sortBy']).' ASC';
            }

        }

        // here we have saved or prebuilt filters
        if(!empty($params['filterId'])){

            $id = $params['filterId'];
            $sql = "SELECT * from filters where filter_id IN ($id)";
            $filter = $this->fetchAllAssoc($sql);
            $filterDetails = json_decode($filter[0]['filter_details'], true);
            // prebuilt filter
            if($filter[0]["filter_id"] > 0 && $filter[0]["filter_id"] <= ALL_JOBS_IN_PROGRESS){
                if(!empty($filterDetails['additional'])){
                    $this->filter .= $filterDetails['additional'];
                    unset($filterDetails['additional']);
                }
                if(!empty($filterDetails['innerJoin'])) {
                    $this->innerJoin .= $filterDetails['innerJoin'];
                }
                if($filter[0]["filter_id"] == MY_JOBS_IN_PROGRESS){
                    $sysId = $this->app->service('user')->getCurrentUser()->getUserSysid();
                    $filterDetails["sysid"] = $sysId;
                    $this->filter .= "AND j.job_jst_id <> $closedStatus";
                }
                if($filter[0]["filter_id"] == ALL_JOBS_IN_PROGRESS && !empty($params["assigned_to"])){
                    $filterDetails["assignedTo"] = $params["assigned_to"];
                }
                $params = $filterDetails;
            }
            //saved filter, we assigned the filter details to our paramters
            else{
                $params = $filterDetails;
            }
        }

        // paramters passed in from the filter_options
        if (!empty($params['jobTypeId'])) {
            $this->filter .= " AND j.job_jty_id = :job_type_id ";
            $this->sqlParams[':job_type_id'] = $params['jobTypeId'];
        }

        // paramters passed in from the filter_options
        if (!empty($params['jobCommentFlag']))
        {
            if(strpos($params['jobCommentFlag'], '|'))
            {
                $params['jobCommentFlag'] = str_replace('|', ',', $params['jobCommentFlag']);
                $this->filter = ' j.job_comment_flag in(' .  $params['jobCommentFlag'] . ') ';
            }
        }

        if (!empty($params['jobStatus'])) {
            // if > 0 filter based on jobstatus from jobstatus table
            // if < 0 use default filter (it is a prebuild filter)
            if ($params['jobStatus'] > 0) {
                $this->filter .= " AND j.job_jst_id = :job_status ";
                $this->sqlParams[':job_status'] = $params['jobStatus'];
            } else {
                $this->filter .= "AND j.job_jst_id <> $cadAssignedStatus AND j.job_jst_id <> $closedStatus";
            }
        }

        if (!empty($params['referenceNumber'])) {
            $this->filter .= " AND j.job_reference_no = :reference_no ";
            $this->sqlParams[':reference_no'] = $params['referenceNumber'];
            // Only return one result since you're expecting a distinct result
            $this->numberOfResults = 1;
        }

        if (!empty($params['advertiserId'])) {
            $this->filter .= " AND j.job_adv_id = :job_adv_id ";
            $this->sqlParams[':job_adv_id'] = $params['advertiserId'];

            // Filter conditions for retrieving the unattached pre check list
            if (isset($params['unattachedPreCheckList'])) {
                $this->sqlParams[':job_precheck_adv_id'] = $params['advertiserId'];
                $this->sqlParams[':pre_check_id'] = $this->preCheckId;
                $this->filter .= ' AND j.job_submission_date > DATEADD(year, -1, GetDate()) ' .
                    ' AND job_id NOT IN (
                                    SELECT
                                    job_reference_no_precheck_id_link
                                    FROM [dbo].[jobs]
                                    WHERE job_adv_id = :job_precheck_adv_id
                                    AND job_jty_id != :pre_check_id
                                    AND job_reference_no_precheck_id_link IS NOT NULL ) ';
            }
        }

        if (!empty($params['keyNumber'])) {
            $this->filter .= " AND t.tvc_key_no LIKE :key_number ";
            $this->sqlParams[':key_number'] = '%'.$params['keyNumber'].'%';
        }

        if (!empty($params['description'])) {
            $this->filter .= " AND UPPER(t.tvc_product_description) LIKE UPPER(:description) ";
            $this->sqlParams[':description'] = '%' . $params['description'] . '%';
        }

        if (!empty($params['advertiserName'])) {
            $advName = $params['advertiserName'];
            $this->filter .= " AND UPPER(ad.adv_name) like UPPER(:advertiser_name) ";
            $this->sqlParams[':advertiser_name'] = '%' . $advName . '%';
        }

        if (!empty($params['agencyName'])) {
            $agName = $params['agencyName'];
            $this->filter .= " AND UPPER(ag.ag_name) like UPPER(:agency_name) ";
            $this->sqlParams[':agency_name'] = '%' . $agName . '%';
        }

        if (!empty($params['$params'])) {
            $agName = $params['agencyName'];
            $this->filter .= " AND j.assigned_to like :agency_name ";
            $this->sqlParams[':agency_name'] = '%' . $agName . '%';
        }

        if(!empty($params['sysid'])){
            $this->filter .= " AND (j.job_assigned_user = :sysid1 OR job_owner = :sysid2) ";
            $this->sqlParams[':sysid1'] = $params['sysid'];
            $this->sqlParams[':sysid2'] = $params['sysid'];
        }

        if (!empty($params['assignedTo'])) {
            $assignedTo = trim($params['assignedTo']);
            // we do a like on (user_first_name+user_last_name OR user_name OR user_id)
            $this->filter .= " AND (u.user_first_name +' '+u.user_last_name LIKE :assigned_to ";
            $this->filter .= " OR u.user_name LIKE :assigned_to_1 ";
            $this->filter .= " OR u.user_id LIKE :assigned_to_2 )";
            $this->sqlParams[':assigned_to'] = '%' . $assignedTo . '%';
            $this->sqlParams[':assigned_to_1'] = '%' . $assignedTo . '%';
            $this->sqlParams[':assigned_to_2'] = '%' . $assignedTo . '%';
        }

        if (!empty($params['dueBefore'])) {

            $dueBefore = date('Y-m-d', strtotime($params['dueBefore']))." 23:59:59";

            $this->filter .= " AND DATEDIFF ( day , j.job_required_by_date, :due_before ) > 0 ";
            $this->sqlParams[':due_before'] = $dueBefore;
        }

        if (!empty($params['dueAfter'])) {

            $dueAfter = date('Y-m-d', strtotime($params['dueAfter']));

            $this->filter .= " AND DATEDIFF ( day , j.job_required_by_date, :due_after ) <= 0 ";
            $this->sqlParams[':due_after'] = $dueAfter;
        }

        // cad number format =  [C][NNNN][AA][T] // ex: GPR51PSA
        if (!empty($params['CADNumber'])) {
//            In the current implementation, the CAD number is stored directly in the CAD number column, if this changes back to what it was before, this code will be useful again
//            $calssification = substr($params['CADNumber'], 0, 1);
//            $CADNumber = substr($params['CADNumber'], 1, 4);
//            $advCategory = substr($params['CADNumber'], 5, 2);
//            $contentCode = substr($params['CADNumber'], 7);

//            $this->filter .= " AND t.tvc_cad_no = :CADNumber AND t.tvc_classification_code = :classification AND ad.adv_default_advertiser_category = :advCategory AND t.tvc_content_code = :contentCode";
            $this->filter .= " AND t.tvc_cad_no = :CADNumber";

//            $this->sqlParams[':classification'] = $calssification;
//            $this->sqlParams[':CADNumber'] = $CADNumber;
//            $this->sqlParams[':advCategory'] = $advCategory;
//            $this->sqlParams[':contentCode'] = $contentCode;
            $this->sqlParams[':CADNumber'] = $params['CADNumber'];

            //don't need this one
            // $this->leftJoin .= 'LEFT JOIN dbo.classification cla ON  cla.Code = t.Classification ';
        }

        if (!empty($params['paymentType'])) {
            $this->filter .= " AND j.job_pme_id = :payment_method ";
            $this->sqlParams[':payment_method'] = $params['paymentType'];
            $this->leftJoin .= ' LEFT JOIN dbo.payment_methods pm on j.job_pme_id = pm.pme_id '; //luca TODO can I remove this shitty thingy
        }

        if (!empty($params['deliveryMethod'])) {

            $this->filter .= " AND t.tvc_format_code = :delivery_method ";
            $this->sqlParams[':delivery_method'] = $params['deliveryMethod'];
        }

        if (!empty($params['revision'])) {
            if ($params['revision'] == 'yes') {
                $this->filter .= " AND (t.tvc_original_tvc_id IS NOT NULL OR NULLIF(j.job_revisions_details,'') IS NOT NULL OR (j.job_jty_id = $agencyFeedbackStatus OR j.job_jty_id = $cadAssignedStatus))";
            } else {
                $this->filter .= " AND t.tvc_original_tvc_id IS NULL AND j.job_jty_id NOT IN ($agencyFeedbackStatus,$cadAssignedStatus) and tvcr.tvc_job_id is null";
            }
        }

        if(!empty($params['invoiceId'])) {
            $this->filter .= " AND inv.inv_id = :invoiceId ";
            $this->sqlParams[':invoiceId'] = $params['invoiceId'];
            $this->leftJoin .= ' LEFT JOIN dbo.invoices inv on j.job_id = inv.inv_job_id ';
        }

        if (!empty($params['assignedUserId'])) {
            $this->filter .= " AND j.job_assigned_user = :assignedUserId ";
            $this->sqlParams[':assignedUserId'] = $params['assignedUserId'];
        }

        if (!empty($params['redHotJob'])) {
            if ($params['redHotJob'] == 'yes') {
                $this->filter .= " AND j.job_red_hot = 1 ";
            } else {
                $this->filter .= " AND ( j.job_red_hot <> 1 OR j.job_red_hot IS NULL ) ";
            }
        }


        if (!empty($params['redHotAlert'])) {
            $this->filter .= "AND j.job_jst_id <> $cadAssignedStatus AND j.job_jst_id <> $closedStatus ";
        }

        // This variable tracks if the SQL condition needs to be an AND or an OR in case a joblist request is sent in with multiple parameters
        $restrictionCounter = 0;
        if (array_key_exists('own',$params)) {
            if ($restrictionCounter == 0) {
                $this->filter .= " AND j.job_created_by = :jobSubmitter ";
            } elseif ($restrictionCounter > 0) {
                $this->filter .= " OR j.job_submitted_by = :jobSubmitter ";
            }
            $this->sqlParams[':jobSubmitter'] = $this->app->service('user')->getCurrentUser()->getUserSysid();
            $restrictionCounter++;
        }

        if (array_key_exists('linked',$params)) {
            if ($restrictionCounter == 0) {
                $this->filter .= " AND j.job_ag_id IN (SELECT aau_ag_id FROM dbo.agency_agency_user WHERE aau_agu_id = :agencyUserId) ";
            } elseif ($restrictionCounter > 0) {
                $this->filter .= " OR j.job_ag_id IN (SELECT aau_ag_id FROM dbo.agency_agency_user WHERE aau_agu_id = :agencyUserId) ";
            }
            $currentAgencyUser = $this->app->service('User')->getCurrentUser()->getAgencyUser();
            $this->sqlParams[':agencyUserId'] = $currentAgencyUser['userId'];
            $restrictionCounter++;
        }

        if (array_key_exists('agency',$params)) {
            if ($restrictionCounter == 0) {
                $this->filter .= " AND j.job_ag_id = :agencyId ";
            } elseif ($restrictionCounter > 0) {
                $this->filter .= " OR j.job_ag_id = :agencyId ";
            }
            $currentAgencyUser = $this->app->service('User')->getCurrentUser()->getAgencyUser();
            $this->sqlParams[':agencyId'] = $currentAgencyUser['agencyId'];
        }

        if(!empty($params['draft'])){
            $this->filter .= " AND job_submission_date is null ";
        }
        elseif(!empty($params['withDrafts'])){

        }
        else{
            $this->filter .= " AND job_submitted_by IS NOT NULL ";
        }

        /* set a default we should almost never get here */
        if ($this->filter == "1=1 ") {
            $this->filter .= "AND j.job_jst_id <> $cadAssignedStatus AND j.job_jst_id <> $closedStatus";
        }
    }

    public function fetch() {
        $filter = $this->filter;
        $leftJoin = $this->leftJoin;
        $innerJoin = $this->innerJoin;
        $numberOfResults = $this->numberOfResults;

        if($this->clientId != 1 && empty($this->order)) {
            $this->order = "ORDER BY job_modify_date DESC";
        }
        else if(empty($this->order)){
            $this->order = "ORDER BY job_id DESC";
        }

        $sql = " SELECT
    	TOP {$numberOfResults}
    	j.job_id,
    	j.job_description,
    	j.job_title,
    	j.job_reference_no,
    	j.job_submitted_by,
    	j.job_jst_id,
        MAX(j.job_adv_id) as job_adv_id,
    	ad.adv_name,
    	ag.ag_code,
    	js.jst_name,
    	jt.jty_name,
    	jt.jty_id,
    	j.job_read_flag,
    	COALESCE(u.user_first_name, '') + ' ' + COALESCE(u.user_last_name, '') as assigned_username,
    	j.job_action_by_date,
    	LEFT(CAST(j.job_revisions_details as NVARCHAR(MAX)), 64) as job_revisions_details,
    	ag.ag_scr_id,
    	sc.scr_reason,
    	j.job_submission_date,
        j.job_parent_id,
        ag.ag_is_approved,
        ad.adv_is_approved,
        tvcr.tvc_job_id as revisedJobId,
        j.job_red_hot,
        CASE WHEN tvcr2.tvc_late_fee IS NULL THEN 0 ELSE tvcr2.tvc_late_fee END AS tvc_late_fee

    	FROM 		dbo.jobs AS j
    	LEFT JOIN dbo.tvcs AS t ON t.tvc_job_id = j.job_id
    	LEFT JOIN (select distinct t1.tvc_job_id from tvcs t1 where tvc_original_tvc_id is not null ) as tvcr on tvcr.tvc_job_id = j.job_id
    	LEFT JOIN (select t2.tvc_job_id, t2.tvc_late_fee from tvcs t2 ) as tvcr2 on tvcr2.tvc_job_id = j.job_id
        {$innerJoin}
    	-- LEFT JOIN dbo.requirements_tvc AS r ON r.rtv_reference_no = t.tvc_reference_no
    	JOIN dbo.advertisers ad ON ad.adv_id = j.job_adv_id
    	JOIN dbo.agencies ag ON j.job_ag_id = ag.ag_id
    	LEFT JOIN dbo.stop_credits sc ON ag.ag_scr_id = sc.scr_id
    	LEFT JOIN dbo.users u ON j.job_owner = u.user_sysid
    	JOIN dbo.job_types jt ON j.job_jty_id = jt.jty_id
    	LEFT JOIN dbo.job_statuses js ON j.job_jst_id = js.jst_id

        {$leftJoin}
    	WHERE
    	{$filter}
    	-- AND j.job_action_by_date is not NULL
    	GROUP BY
    	j.job_id,
    	j.job_jst_id,
    	j.job_reference_no,
    	js.jst_name,
    	jt.jty_name,
        jt.jty_id,
    	j.job_read_flag,
    	j.job_submitted_by,
        j.job_revisions_details,
    	u.user_first_name,
    	u.user_last_name,
    	j.job_action_by_date,
    	ad.adv_name,
    	ag.ag_code,
    	ag.ag_scr_id,
    	sc.scr_reason,
    	j.job_submission_date,
        j.job_parent_id,
        ag.ag_is_approved,
        ad.adv_is_approved,
        tvcr.tvc_job_id,
        j.job_description,
        j.job_title,
        j.job_modify_date,
        j.job_red_hot,
        CASE WHEN tvcr2.tvc_late_fee IS NULL THEN 0 ELSE tvcr2.tvc_late_fee END

        {$this->order}
    	";
        $data = $this->fetchAllAssoc($sql, $this->sqlParams);

        //clean up the sql params once we used them
        $this->sqlParams = '';
        $jobList = array();

        // sorry for the nested statements, can't make setters/getters as it's all in one list variable
        if(empty($data)){
            $data['responseCode'] = 404;
            $this->list = array(array());
            return false;
        }
        foreach($data as $id => $job) {
            $jobList[$id] = array();

            $keys = array_keys($job);
            foreach($this->fieldMap as $fieldName => $details) {
                if (in_array($details['name'], $keys)) {
                    if (isset($details['type']) && $details['type'] == 'boolean') {
                        $jobList[$id][$fieldName] = Convert::toBoolean($job[$details['name']]);
                    } else {
                        $jobList[$id][$fieldName] = $job[$details['name']];
                    }
                }
            }
        }

        $this->list = $jobList;
        return true;
    }

    public function searchJobList($query, $restrictions = array(), $filters = array())
    {
        if(!empty($restrictions)){
            foreach($restrictions as $restriction) {
                $filters[$restriction] = 1;
            }
        }
        $this->setParams($filters);

        $filter = $this->filter;
        $leftJoin = $this->leftJoin;
        $innerJoin = $this->innerJoin;

        if(empty($this->order)){
            $this->order = 'ORDER BY job_id DESC';
        }


        $sql = "
            DECLARE @search varchar(50)
            SET @search = :query

            SELECT DISTINCT TOP 1000
            j.job_id,
            j.job_title,
            j.job_jst_id,
            j.job_submission_date,
            ag.ag_id,
            j.job_reference_no,
            js.jst_name,
            j.job_action_by_date,
            ag.ag_scr_id,
            sc.scr_reason,
            jt.jty_name,
            jt.jty_id,
            u.user_name as assigned_username,
            j.job_adv_id,
            adv.adv_name,
            adv.adv_code,
            ag_billing_code,
            ag_code
            -- COALESCE(NULLIF(ag_billing_code,''), ag_code) as ag_code
           -- ,
           -- j.job_action_by_date,
           -- t.tvc_classification_code+
           -- t.tvc_cad_no+
           -- adv.adv_code+
           -- t.tvc_content_code AS cad_no,
           -- t.tvc_key_no

            FROM dbo.jobs AS j
            {$innerJoin}
            LEFT JOIN dbo.job_types AS jt ON j.job_jty_id = jt.jty_id
            LEFT JOIN dbo.job_statuses as js ON j.job_jst_id = js.jst_id
            LEFT JOIN dbo.agencies AS ag ON j.job_ag_id = ag.ag_id
    	    LEFT JOIN dbo.stop_credits AS sc ON ag.ag_scr_id = sc.scr_id
            LEFT JOIN dbo.users AS u ON j.job_assigned_user = u.user_sysid
            LEFT JOIN dbo.advertisers AS adv ON j.job_adv_id = adv.adv_id
            {$leftJoin}
            WHERE
            {$filter} AND
            ((t.tvc_classification_code+
            t.tvc_cad_no+
            adv.adv_code+
            t.tvc_content_code) LIKE @search OR
            j.job_reference_no LIKE @search OR
            t.tvc_key_no LIKE @search OR
            ag.ag_code LIKE @search OR
            adv.adv_code LIKE @search OR
            j.job_purchase_order LIKE @search OR
            ag.ag_billing_code LIKE @search or
            j.job_title LIKE @search
            )

            GROUP BY
            j.job_id,
            j.job_title,
            j.job_jst_id,
            j.job_submission_date,
            ag.ag_id,
            j.job_reference_no,
            CAST(j.job_revisions_details as NVARCHAR(MAX)),
            js.jst_name,
            j.job_action_by_date,
            jt.jty_id,
            jt.jty_name,
            u.user_name,
            j.job_adv_id,
            ag.ag_scr_id,
            sc.scr_reason,
            adv.adv_name,
            ag.ag_billing_code,
            adv.adv_code,
            ag.ag_code

            {$this->order}
        ";

        $query = [':query' => '%'.$query.'%'];
        $sqlParams = array_merge($this->sqlParams, $query);

        $data = $this->fetchAllAssoc($sql, $sqlParams);

        $jobList = array();

        if ($data === false) {
            $this->list = false;
            return false;
        }

        // sorry for the nested statements, can't make setters/getters as it's all in one list variable
        foreach($data as $id => $job) {
            $jobList[$id] = array();
            $keys = array_keys($job);
            foreach($this->fieldMap as $fieldName => $details) {
                if (in_array($details['name'], $keys)) {
                    if (isset($details['type']) && $details['type'] == 'boolean') {
                        $jobList[$id][$fieldName] = Convert::toBoolean($job[$details['name']]);
                    } else {
                        $jobList[$id][$fieldName] = $job[$details['name']];
                    }
                }
            }
        }
        $this->list = $jobList;
    }

    public function retrieveJobsWithRestrictions($restrictions, $filters = array())
    {
        foreach($restrictions as $restriction) {
            $filters[$restriction] = 1;
        }
        $this->setParams($filters);
        $this->fetch();
    }

}
