<?php

namespace App\Models;

use Elf\Application\Application;
use Elf\Exception\ConflictException;
use Elf\Exception\NotFoundException;
use App\Utility\Helpers;


class KeyNumber extends \Elf\Db\AbstractAction {

    const STATUS_PENDING = 'Pending';
    const STATUS_CLASSIFIED = 'Classified';
    const STATUS_WITHDRAWN = 'Withdrawn';

    private $tvcId;
    private $jobId;
    private $content;
    private $keyNumber;
    private $chargeCode;
    private $cadNumber;
    private $generatedCadNumber;
    private $expiryDate;
    private $assignedBy;
    private $assignedById;
    private $assignedDate;
    private $description;
    private $length;
    private $eventType;
    private $tvcDelivery;
    private $tvcDeliveryCode;
    private $op48;
    private $classification;
    private $productCategoryCode;
    private $lateFee;
    private $originalTvcId;
    private $originalKeyNumber;
    private $originalJobId;
    private $cTime;
    private $tvcManuallyExpired;
    private $isOverride;
    private $transactionId;
    private $tvcToAir;
    private $tvcType;
    private $withdrawnDate;
    private $tvcInvoiceId;
    private $tvcDAREvent;
    private $tvcDAREventDate;
    private $tvcTotalCharge;
    private $tvcChargeExGST;
    private $tvcChargeGST;

    protected $fieldMap = array (
        'tvcId' => array (
            'name' => 'tvc_id',
            'type' => 'numeric',
            'required' => false,
            'allowEmpty' => true,
        ),
        'jobId' => array (
            'name' => 'tvc_job_id',
            'type' => 'numeric',
            'required' => true,
            'allowEmpty' => true,
        ),
        'content' => array (
            'name' => 'tvc_content_code',
            'type' => 'string',
            'required' => true,
            'allowEmpty' => true,
        ),
        'keyNumber' => array (
            'name' => 'tvc_key_no',
            'type' => 'string',
            'required' => true,
            'allowEmpty' => true,
        ),
        'chargeCode' => array (
            'name' => 'tvc_charge_code',
            'type' => 'numeric',
            'required' => true,
            'allowEmpty' => false,
        ),
        'generatedCadNumber' => array (
            'name' => 'tvc_cad_number',
            'type' => 'string',
            'required' => false,
            'allowEmpty' => true,
        ),
        'cadNumber' => array (
            'name' => 'tvc_cad_no',
            'type' => 'string',
            'required' => false,
            'allowEmpty' => true,
        ),
        'expiryDate' => array (
            'name' => 'tvc_expiry_date',
            'type' => 'string',
            'required' => false,
            'allowEmpty' => true,
        ),
        'assignedBy' => array (
            'name' => 'tvc_cad_assigned_by_name',
            'type' => 'string',
            'required' => false,
            'allowEmpty' => true,
            'protected' => true,
        ),
        'assignedById' => array (
            'name' => 'tvc_cad_assigned_by',
            'type' => 'numeric',
            'required' => false,
            'allowEmpty' => true,
            'protected' => true,
        ),
        'assignedDate' => array (
            'name' => 'tvc_cad_assigned_date',
            'type' => 'string',
            'required' => false,
            'allowEmpty' => true,
            'protected' => true,
        ),
        'description' => array (
            'name' => 'tvc_product_description',
            'type' => 'string',
            'required' => true,
            'allowEmpty' => true,
        ),
        'productCategoryCode' => array (
            'name' => 'tvc_product_category_code',
            'type' => 'numeric',
            'required' => false,
            'allowEmpty' => true,
        ),
        'length' => array (
            'name' => 'tvc_length',
            'type' => 'string',
            // Not required for infomercial tvcs
            'required' => false,
            'allowEmpty' => true,
        ),
        'eventType' => array (
            'name' => 'tvc_event_type',
            'type' => 'string',
            'required' => false,
            'allowEmpty' => true,
        ),
        'op48' => array (
            'name' => 'tvc_op48',
            'type' => 'boolean',
            'required' => false,
            'allowEmpty' => true,
        ),
        'classification' => array (
            'name' => 'tvc_classification_code',
            'type' => 'string',
            'required' => false,
            'allowEmpty' => true,
        ),
        'tvcDeliveryCode' => array (
            'name' => 'tvc_format_code',
            'type' => 'string',
            'required' => false,
            'allowEmpty' => true,
        ),
        'lateFee' => array (
            'name' => 'tvc_late_fee',
            'type' => 'boolean',
            'required' => false,
            'allowEmpty' => true,
        ),
        'originalKeyNumber' => array (
            'name' => 'tvc_original_key_number',
            'type' => 'string',
            'required' => false,
            'allowEmpty' => true,
        ),
        'originalJobId' => array (
            'name' => 'tvc_original_tvc_job_id',
            'type' => 'numeric',
            'required' => false,
            'allowEmpty' => true,
        ),
        'originalTvcId' => array (
            'name' => 'tvc_original_tvc_id',
            'type' => 'numeric',
            'required' => false,
            'allowEmpty' => true,
        ),
        'cTime' => array (
            'name' => 'tvc_ctime',
            'type' => 'boolean',
            'required' => false,
            'allowEmpty' => true,
        ),
        'tvcManuallyExpired' => array (
            'name' => 'tvc_manually_expired',
            'type' => 'string',
            'required' => false,
            'allowEmpty' => true,
        ),
        'isOverride' => array (
            'name' => 'tvc_override',
            'type' => 'boolean',
            'required' => false,
            'allowEmpty' => true,
        ),
        'transactionId' => array (
            'name' => 'tvc_transaction_id',
            'type' => 'numeric',
            'required' => false,
            'allowEmpty' => true,
        ),
        'withdrawnDate' => array (
            'name' => 'tvc_cad_withdrawn_date',
            'type' => 'string',
            'required' => false,
            'allowEmpty' => true,

        ),
        'tvcToAir' => array (
            'name' => 'tvc_to_air',
            'type' => 'string',
            'required' => false,
            'allowEmpty' => true,
        ),
        'tvcType' => array (
            'name' => 'tvc_type',
            'type' => 'string',
            'required' => false,
            'allowEmpty' => true,
        ),
        'tvcInvoiceId' => array (
            'name' => 'tvc_inv_id',
            'type' => 'numeric',
            'required' => false,
            'allowEmpty' => true,
        ),
        'tvcDAREvent' => array(
            'name' => 'tvc_dar_event',
            'type' => 'string',
            'required' => false,
            'allowEmpty' => true,
        ),
        'tvcDAREventDate' => array(
            'name' => 'tvc_dar_event_date',
            'type' => 'string',
            'required' => false,
            'allowEmpty' => true,
        ),
        'tvcTotalCharge' => array (
            'name' => 'tvc_total_charge',
            'type' => 'double',
            'ignoreForValidation' => true,
        ),
        'tvcChargeExGST' => array (
            'name' => 'tvc_charge_ex_gst',
            'type' => 'double',
            'ignoreForValidation' => true,
        ),
        'tvcChargeGST' => array (
            'name' => 'tvc_charge_gst',
            'type' => 'double',
            'ignoreForValidation' => true,
        )
    );


    ///// All the setters and getters
    public function setFromArray($data,$post = false)
    {
        foreach ($this->fieldMap as $key => $mapping) { //loop through the field map and only pick up data that we want
            if(empty($mapping['protected']) || $post == false) {
                $setMethod = "set" . ucfirst($key);
                if (method_exists($this, $setMethod) && isset($data[$mapping['name']])) { // check if we can actually update this field
                    $this->$setMethod($data[$mapping['name']]);
                } else if (method_exists($this, $setMethod) && isset($data[$key])) { // check if we can actually update this field
                    $this->$setMethod($data[$key]);
                }
            }
        }
    }

    public function deleteTvc()
    {
        $sql = "DELETE FROM dbo.tvcs ";
        $rows = 0;
        if ($this->getTvcId()) {
            $sql .= " WHERE  tvc_id = :tvcId";
            $params = array (
                ":tvcId" => $this->tvcId,
            );
            $rows = $this->delete($sql,$params);
        }
        if ($rows === 0) {
            return false;
        }
        return true;
    }

    public function getTvcById($id)
    {
        $this->setTvcId($id);
        $this->load();
    }

    public function assignCadNumber($tvcId,$unsetTransactionFailureFlag = false)
    {
        // Start logging
        $loggingService = $this->app->service('Logger');
        $loggingService->setFilename('ConfirmFinalOrderForm');
        $loggingService->info('KeyNumber::assignCadNumber');

        // Load the tvc in question
        $this->setTvcId($tvcId);
        $this->load();
        $loggingService->info('KeyNumber::assignCadNumber Load TVCs');

        // Check if the job is a pre-check
        $jobId = $this->getJobId();
        $jobModel = $this->app->model('job');
        $jobModel->setJobId($jobId);
        $jobModel->load();
        if($jobModel->isPrecheck() == true) {
            $loggingService->info('KeyNumber::assignCadNumber Updating precheck');

            $sql = "UPDATE dbo.tvcs SET
                  tvc_cad_no = 'processed',
                  tvc_cad_assigned_by = {$this->app->service('user')->getCurrentUser()->getUserSysid()},
                  tvc_cad_assigned_date = GETDATE(),
                  tvc_dar_event = '".self::STATUS_CLASSIFIED."', -- Set DAR fields
                  tvc_dar_event_date = GETDATE()
                WHERE tvc_id = :tvcId";

            $this->execute($sql,array(':tvcId' => $tvcId));
            $loggingService->info('KeyNumber::assignCadNumber Precheck updated');
            return true;
        }
        // For the unique numeric key, figure out where the key has reached for a particular classification code
        // If the key number has been rejected, then mark it as processed
        if($this->eventType == 'R') {
            $loggingService->info('KeyNumber::assignCadNumber Updating rejected key number');
            $sql = "UPDATE dbo.tvcs SET
                      tvc_rejection_processed = 1,
                      tvc_cad_assigned_by = {$this->app->service('user')->getCurrentUser()->getUserSysid()},
                      tvc_dar_event = '".self::STATUS_CLASSIFIED."', -- Set DAR fields
                      tvc_dar_event_date = GETDATE()
                    WHERE tvc_id = :tvcId";
            $this->execute($sql,array(':tvcId' => $tvcId));
            $loggingService->info('KeyNumber::assignCadNumber Rejected key number updated');
        }

        $loggingService->info('KeyNumber::assignCadNumber Getting max tvc_cad_unique_numeric_key');
        $sql = "SELECT MAX(CAST(tvc_cad_unique_numeric_key AS Int)) as numericKey
                FROM dbo.tvcs
                WHERE tvc_classification_code = :classificationCode";

        $data = $this->fetchOneAssoc($sql,array(':classificationCode' => $this->classification));
        $loggingService->info('KeyNumber::assignCadNumber Max tvc_cad_unique_numeric_key: ' . json_encode($data));

        // The numeric key can't exceed this number which translates to 'zzzz' in Base36 format
        if($data['numericKey'] >= 1679615) {
            $loggingService->error('CAD Number count has been exceeded for this classification');
            throw new \Exception('CAD Number count has been exceeded for this classification');
        }

        // Assemble the CAD number
        $uniqueNumericCount = $data['numericKey'] + 1;
        $alphaNumericCadIdentifier = strtoupper(base_convert($uniqueNumericCount, 10, 36));

        // First get the advertiser category/product category code
        $loggingService->info('KeyNumber::assignCadNumber Getting advertiser category/product category code');
        $advertiserCategoryModel = $this->app->model('advertiserCategory');
        $advertiserCategoryModel = $advertiserCategoryModel->findCategoryById($this->productCategoryCode);
        $advertiserCategory = $advertiserCategoryModel->getAsArray();
        $advertiserCategoryCode = $advertiserCategory['advertiserCode'];
        $loggingService->info('KeyNumber::assignCadNumber Advertiser category code: ' . $advertiserCategory['advertiserCode']);

        // Validate for government expiry dates
        $loggingService->info('KeyNumber::assignCadNumber Calculating expiry date');
        $expiryDate = $this->calculateExpiryDate();

        // Put everything together, this is done under the assumption that this is already a valid key number and these fields have been filled
        $cadNumber = $this->classification . str_pad($alphaNumericCadIdentifier,$this->app->config->cadLength,"0",STR_PAD_LEFT) . $advertiserCategoryCode . $this->content;

        $sqlAddition = '';
        if($unsetTransactionFailureFlag == true) {
            $sqlAddition .= ', tvc_payment_failure = 0 ';
        }

        if(!empty($this->isOverride)){
            $sqlAddition .= ', tvc_override = 1 ';
        }

        // Get user ID
        $userSysId = $this->app->service('user')->getCurrentUser()->getUserSysid();

        $loggingService->info('KeyNumber::assignCadNumber Updating TVC ' . $tvcId . ' with CAD number ' . $cadNumber . ' by user ID ' . $userSysId);

        // Log active DB connections
        try {
            $this->logActiveDBConnections();
        } catch ( \Exception $e ) {
            $loggingService->error('KeyNumber::assignCadNumber Error logging active DB connections. Exception: ' . $e->getMessage());
        }

        $sql = "UPDATE dbo.tvcs SET
                  tvc_cad_no = :cadNumber,
                  tvc_cad_assigned_by = :userSysId,
                  tvc_cad_assigned_date = GETDATE(),
                  tvc_cad_unique_numeric_key = :uniqueNumericCount,
                  tvc_expiry_date = {$expiryDate},
                  tvc_dar_event = :dar_event, -- Set DAR fields
                  tvc_dar_event_date = GETDATE()
                  {$sqlAddition}
                WHERE tvc_id = :tvcId";

        $loggingService->info('KeyNumber::assignCadNumber SQL: ' . $sql);

        $this->execute($sql,[
            ':userSysId' => $userSysId,
            ':dar_event' => self::STATUS_CLASSIFIED,
            ':cadNumber' => $cadNumber,
            ':uniqueNumericCount' => $uniqueNumericCount,
            ':tvcId' => $tvcId]);
        $loggingService->info('KeyNumber::assignCadNumber TVCs updated');
        return true;
    }

    /**
     * Log active DB connections
     *
     */
    private function logActiveDBConnections()
    {
        $connections = $this->getActiveDBConnections();
        $total = 0;
        $msg = '';

        foreach ( $connections as $aConnection ) {
            $msg .= $aConnection['ConnectionStatus'] . ' ' . $aConnection['isSleeping'] . ': ' . $aConnection['ConnectionCount'] . ' | ';
            $total += $aConnection['ConnectionCount'];
        }

        // Total number of connections
        $msg .= 'Total: ' . $total;

        $loggingService = $this->app->service('Logger');
        $loggingService->setFilename('ConfirmFinalOrderForm');
        $loggingService->info('KeyNumber::logActiveDBConnections DB Connections: ' . $msg);
    }

    /**
     * Get active DB connections
     *
     */
    private function getActiveDBConnections()
    {
        $sql = "SELECT ConnectionStatus = CASE WHEN dec.most_recent_sql_handle = 0x0
                    THEN 'Unused'
                    ELSE 'Used'
                    END
                , isSleeping = CASE WHEN des.status = 'Sleeping'
                    THEN 'sleeping'
                    ELSE 'Not Sleeping'
                    END
                , ConnectionCount = COUNT(1)
            FROM sys.dm_exec_connections dec
                INNER JOIN sys.dm_exec_sessions des ON dec.session_id = des.session_id
            GROUP BY CASE WHEN des.status = 'Sleeping'
                    THEN 'sleeping'
                    ELSE 'Not Sleeping'
                    END
                , CASE WHEN dec.most_recent_sql_handle = 0x0
                    THEN 'Unused'
                    ELSE 'Used'
                    END;";

        $result = $this->fetchAllAssoc($sql);

        return $result;
    }

    public function markAsFailedTransaction($tvcId)
    {
        $sql = "UPDATE dbo.tvcs SET tvc_payment_failure = 1 WHERE tvc_id = :tvcId";

        $this->execute($sql,array(':tvcId' => $tvcId));
        return;
    }

    /**
     * @param $targets
     * @throws \Exception
     */
    public function  manuallyExpireKeyNumbers($targets)
    {
        foreach ($targets as $key => $target) {
            $sql = "
                UPDATE dbo.tvcs SET
                  tvc_manually_expired = 1
                WHERE tvc_id = ?";
            $this->execute($sql,array($target));
        }
    }

    /**
     * @param $tvcToExtend
     * @throws \Exception
     * @return string
     */
    public function  manuallyExtendExpiryByKeyNumbers($tvcToExtend)
    {
        $advertiserCategoryModel = $this->app->model('advertiserCategory');
        $params = array (
            ':tvcId' => $tvcToExtend,
            ':productCategoryCode' => intval($advertiserCategoryModel->findGovernmentCategoryId())
        );

        $sql = "
                SELECT tvc_cad_assigned_date, tvc_expiry_date
				FROM dbo.tvcs
				WHERE tvc_id = :tvcId
				AND tvc_product_category_code = :productCategoryCode";
        $data = $this->fetchOneAssoc($sql,$params);

        if(empty($data)){
            return "No government tvc's with that id";
        }

        $originalExpiryDate = \DateTime::createFromFormat('Y-m-d H:i:s.u', $data['tvc_expiry_date']);
        $maximumExpiryDate = \DateTime::createFromFormat('Y-m-d H:i:s.u', $data['tvc_cad_assigned_date']);
        $maximumExpiryDate = $maximumExpiryDate->add(new \DateInterval('P2Y'));
        $maximumExpiryDate = $maximumExpiryDate->sub(new \DateInterval('P1D'));

        if($originalExpiryDate == $maximumExpiryDate) {
            return "This Key Number has reached the maximum expiry date already";
        }

        if ($originalExpiryDate < $now) {
            throw new \Exception('Original Key Number has expired.');
        }

        //make new expiry date 90days from the current expiry date
        $newExpiryDate = $originalExpiryDate->add(new \DateInterval('P90D'));

        //if new expiry date is bigger then the maximum expiry date then make the new one = max(me)
        if($newExpiryDate > $maximumExpiryDate) {
            $newExpiryDate = $maximumExpiryDate;
        }

        //update expiry
        $sql = "
                UPDATE dbo.tvcs SET
                  tvc_expiry_date = :expiryDate
                WHERE tvc_id = :tvcId";
        $this->execute($sql,array(':tvcId' => $tvcToExtend, ':expiryDate' => $newExpiryDate->format('Y-m-d H:i')));

        return "Successfully finished updating expiy";

    }

    /**
     * Calculate what the expiry date should be set to for a key number
     * @throws \Exception
     * @return string
     */
    public function calculateExpiryDate()
    {
        $advertiserCategoryModel = $this->app->model('advertiserCategory');
        // If it's a revision, check the original dates.
        if(!empty($this->originalTvcId)) {
            $sql = "SELECT DATEADD(year, 2, tvc_cad_assigned_date) as expiryDate
                FROM dbo.tvcs
                WHERE tvc_id = :tvcId";

            $data = $this->fetchOneAssoc($sql,array(':tvcId' => $this->originalTvcId));
            $now = new \DateTime();
            $originalExpiryDate = \DateTime::createFromFormat('Y-m-d H:i:s.u',$data['expiryDate']);

            // In case this ever happens, it shouldn't

            if ($originalExpiryDate < $now) {
                throw new \Exception('Original Key Number has expired.');
            }

            // If the product category code is that of government
            if($this->productCategoryCode == $advertiserCategoryModel->findGovernmentCategoryId()) {
                $ninetyDaysFromNow = new \DateTime();
                $ninetyDaysFromNow = $ninetyDaysFromNow->add(new \DateInterval('P90D'));

                // If the 90 day government expiry is outside of the 2 year expiration date, set the expiry to be the two year date
                if($originalExpiryDate < $ninetyDaysFromNow) {
                    return "'" . $originalExpiryDate->format('Y-m-d H:i') . "'";
                } else {
                    return "'" . $ninetyDaysFromNow->format('Y-m-d H:i') . "'";
                }
            }
            // Else just set the expiry date to be the original key number's expiry date
            return "'" . $originalExpiryDate->format('Y-m-d H:i') . "'";
        }
        //check for government category code on first assignment
        if($this->productCategoryCode == $advertiserCategoryModel->findGovernmentCategoryId()) {
            return 'DATEADD(day, 90, GETDATE())';

        }
        // Else just set the expiry date to be 2 years from now
        return 'DATEADD(year, 2, GETDATE())';
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

    /**
     * Validate charge codes
     * list of charge codes will be based on job submission date
     * @return bool
     */
    private function validAssignedChargeCode(){

        // get job to figure out the submission date of the job.
        $jobModel = $this->app->model('job');
        $jobModel->setJobId($this->jobId);
        $jobModel->load();

        // For an existing job
        if (!empty($jobModel->getSubmissionDate())) {
            $submittedDate = \DateTime::createFromFormat('Y-m-d H:i:s.u', $jobModel->getSubmissionDate());
            // For a new job
        } else {
            $submittedDate = new \DateTime();
        }

        $chargeCodes = $this->app->model('chargeCode');
        $chargeCodes->setSubmittedDate($submittedDate->format('Y-m-d H:i:s'));
        $chargeCodes->setExcludeActiveCheck(true);
        $effectiveChargeCodes = $chargeCodes->getEffectiveChargeCodes();

        $return = false;
        foreach($effectiveChargeCodes as $effectiveChargeCode){
            if($effectiveChargeCode['chargeCodeId'] == $this->chargeCode){
                $return = true;
                break;
            }
            if($return === true){
                break;
            }
        }

        return $return;
    }

    public function save() {

        if (!$this->validAssignedChargeCode()){
            throw new \Exception("Invalid Charge Code");
        }

        if (!$this->getTvcId()) {
            return $this->create();
        } else {

            return $this->updateTvc();
        }
    }

    public function load() {
        if($this->getTvcId()) {
            $params = array (
                ':tvcId' => $this->tvcId,
            );
            $sql = "SELECT
                           tvc_id
                          ,tvc_job_id
                          ,tvc_content_code
                          ,tvc_reference_no
                          ,tvc_key_no
                          ,tvc_charge_code
                          ,(tvc_classification_code + tvc_cad_no + advertiserCategory + tvc_content_code) as tvc_cad_number
                          ,tvc_cad_no
                          ,tvc_product_category_code
                          ,tvc_expiry_date
                          ,COALESCE(u.user_first_name, '') + ' ' + COALESCE(u.user_last_name, '') as tvc_cad_assigned_by_name
                          ,tvc_cad_assigned_by
                          ,tvc_cad_assigned_date
                          ,tvc_product_description
                          ,tvc_length
                          ,tvc_format_code
                          ,tvc_event_type
                          ,tvc_op48
                          ,tvc_classification_code
                          ,tvc_late_fee
                          ,tvc_original_tvc_job_id
                          ,tvc_original_key_number
                          ,tvc_original_tvc_id
                          ,tvc_manually_expired
                          ,tvc_to_air
                          ,tvc_type
                          ,tvc_ctime
                          ,tvc_override
                          ,tvc_transaction_id
                          ,advertiserCategory
                          ,tvc_inv_id
                    FROM dbo.tvcs
                    LEFT JOIN
                        (
                        SELECT
                            dbo.advertisers.adv_default_advertiser_category as advertiserCategory
                            ,dbo.advertisers.adv_id as advertiserId
                        FROM advertisers
                        ) as advertiser_table
                    ON advertiser_table.advertiserId = tvcs.tvc_adv_id
                    LEFT join users u on u.user_sysid = tvcs.tvc_cad_assigned_by
                    WHERE tvc_id = :tvcId";

            $data = $this->fetchOneAssoc($sql,$params);

            if(!empty($data)) {
                $this->setFromArray($data);
                return;
            }
        }
        throw new NotFoundException(['displayMessage' => 'No result set found - please specify a key number id']);
    }

    public function create()
    {
//        Check that job is an amendment, if it is not check for duplicate key numbers

        $sql = "SELECT job_amendment FROM dbo.jobs WHERE job_id = :jobId";

        $jobIsAmendment = $this->fetchOneAssoc($sql, array(':jobId' => $this->jobId));

        if (empty($jobIsAmendment['job_amendment'])) {
            $sql = "SELECT tvc_key_no FROM dbo.tvcs WHERE tvc_key_no = :keyNumber";

            $records = $this->fetchOneAssoc($sql, array(':keyNumber' => $this->keyNumber));

            if ( !empty($records['tvc_key_no']) ) {
                throw new ConflictException("Key number already exists.");
            }
        }

        $sql = "INSERT INTO dbo.tvcs ";
        $tableColumns = '(';
        $tableVariables = 'VALUES(';
        $params = array();

        // When creating a new KeyNumber we always need to set evenType to 'I'
        $this->setEventType('I');

        // Set today's date
        $now = new \DateTime();
        $this->setTvcDAREventDate($now->format('Y-m-d H:i:s'));

        // Set as pending
        $this->setTvcDAREvent(self::STATUS_PENDING);

        foreach ($this->fieldMap as $key => $mapping) {
            $getMethod = "get" . ucfirst($key);
            if (method_exists($this, $getMethod) && isset($this->$key)) {
                $tableColumns .= $mapping['name'] . ',';
                $params[] = $this->$key;
                $tableVariables .= '?,';
            }
        }
        $tableColumns = rtrim($tableColumns, ',') . ') ';
        $tableVariables = rtrim($tableVariables, ',') . ')';

        $sql .= $tableColumns . $tableVariables;

        $id = $this->insert($sql, $params);
        $this->setTvcId($id);
        return true;
    }

    public function updateTvc()
    {
        if ($this->getTvcId()) {
            $sql = 'UPDATE dbo.tvcs SET ';
            $sqlEnding = ' WHERE tvc_id = :tvcId';
            $params = array (
                ':tvcId' => $this->tvcId,
            );
            unset($this->tvcId);
            unset($this->generatedCadNumber);
            unset($this->advertiserCategory);
            unset($this->assignedBy);

            $columnsToBeUpdated = '';
            foreach ($this->fieldMap as $key => $mapping) {
                $getMethod = "get" . ucfirst($key);
                if (method_exists($this, $getMethod) && isset($this->$key)) {
                    $columnsToBeUpdated .= $mapping['name'] . '= :' . $key . ' , ' ;
                    if($this->$key == ""){
                        $this->$key = null;
                    }
                    $params[':'.$key] = $this->$key;
                }
            }
            $columnsToBeUpdated = rtrim($columnsToBeUpdated,', ');
            $sql .= $columnsToBeUpdated . $sqlEnding;

            $this->execute($sql,$params);
            return true;
        }
    }


    /**
     * update the transaction id for a tvc
     */
    public function updateTvcTransactionId($tvcIds)
    {
        if(is_array($tvcIds)){
            $tvcIds = implode(',', $tvcIds);
        }

        if ($this->getTvcId()) {
            $sql = "UPDATE dbo.tvcs SET
                    tvc_transaction_id = :transaction_id
                    WHERE tvc_id  IN ($tvcIds);";

            $params = array(
                ':transaction_id' => $this->transactionId,
            );
            $this->execute($sql,$params);
        }
    }


    public function getOverriddenTvcsByTransactionId($transactionId)
    {
        $sql = "SELECT
                 *
               FROM
                 tvcs
               WHERE tvc_transaction_id = :transaction_id
                 AND tvc_override = 1 ";
        $sqlParams = array(
            ':transaction_id' => $transactionId
        );

        $result = $this->fetchAllAssoc($sql, $sqlParams);
        return $result;
    }



    /**
     * @return mixed
     */
    public function getTvcManuallyExpired()
    {
        return $this->tvcManuallyExpired;
    }

    /**
     * @param mixed $tvcManuallyExpired
     */
    public function setTvcManuallyExpired($tvcManuallyExpired)
    {
        $this->tvcManuallyExpired = $tvcManuallyExpired;
    }

    /**
     * @return mixed
     */
    public function getCTime()
    {
        return Helpers::convertVariableToBool($this->cTime);
    }

    /**
     * @param mixed $cTime
     */
    public function setCTime($cTime)
    {
        $this->cTime = Helpers::convertVariableToBool($cTime);
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
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param mixed $content
     */
    public function setContent($content)
    {
        $this->content = $content;
    }

    /**
     * @return mixed
     */
    public function getKeyNumber()
    {
        return $this->keyNumber;
    }

    /**
     * @param mixed $keyNumber
     */
    public function setKeyNumber($keyNumber)
    {
        //added for FCR-1670
        $keyNumber = str_replace(' ', '',$keyNumber);
        $this->keyNumber = $keyNumber;
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
    public function getCadNumber()
    {
        return $this->cadNumber;
    }

    /**
     * @param mixed $cadNumber
     */
    public function setCadNumber($cadNumber)
    {
        $this->cadNumber = $cadNumber;
    }

    /**
     * @return mixed
     */
    public function getExpiryDate()
    {
        return $this->expiryDate;
    }

    /**
     * @param mixed $expiryDate
     */
    public function setExpiryDate($expiryDate)
    {
        if(!empty($expiryDate)) {
            $expiryDate = new \DateTime($expiryDate);
            $this->expiryDate = $expiryDate->format('Y-m-d H:i:s');
        }
    }

    /**
     * @return mixed
     */
    public function getAssignedBy()
    {
        return $this->assignedBy;
    }

    /**
     * @param mixed $assignedBy
     */
    public function setAssignedBy($assignedBy)
    {
        $this->assignedBy = $assignedBy;
    }

    /**
     * @return mixed
     */
    public function getAssignedById()
    {
        return $this->assignedById;
    }

    /**
     * @param mixed $assignedById
     */
    public function setAssignedById($assignedById)
    {
        $this->assignedById = $assignedById;
    }

    /**
     * @return mixed
     */
    public function getAssignedDate()
    {
        return $this->assignedDate;
    }

    /**
     * @param mixed $assignedDate
     */
    public function setAssignedDate($assignedDate)
    {
        if(!empty($assignedDate)) {
            $assignedDate = new \DateTime($assignedDate);
            $this->assignedDate = $assignedDate->format('Y-m-d H:i:s');
        }
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
    public function getLength()
    {
        return $this->length;
    }

    /**
     * @param mixed $length
     */
    public function setLength($length)
    {
        $this->length = $length;
    }

    /**
     * @return mixed
     */
    public function getEventType()
    {
        return $this->eventType;
    }

    /**
     * @param mixed $eventType
     */
    public function setEventType($eventType)
    {
        $this->eventType = $eventType;
    }

    /**
     * @return mixed
     */
    public function getTvcDelivery()
    {
        return $this->tvcDelivery;
    }

    /**
     * @param mixed $tvcDelivery
     */
    public function setTvcDelivery($tvcDelivery)
    {
        $this->tvcDelivery = $tvcDelivery;
    }

    /**
     * @return mixed
     */
    public function getOp48()
    {
        return Helpers::convertVariableToBool($this->op48);
    }

    /**
     * @param mixed $op48
     */
    public function setOp48($op48)
    {
        $this->op48 = Helpers::convertVariableToBool($op48);
    }

    /**
     * @return mixed
     */
    public function getClassification()
    {
        return $this->classification;
    }

    /**
     * @param mixed $classification
     */
    public function setClassification($classification)
    {
        $this->classification = $classification;
    }

    /**
     * @return mixed
     */
    public function getTvcId()
    {
        return $this->tvcId;
    }

    /**
     * @param mixed $tvcId
     */
    public function setTvcId($tvcId)
    {
        $this->tvcId = (int) $tvcId;
    }

    /**
     * @return mixed
     */
    public function getGeneratedCadNumber()
    {
        return $this->generatedCadNumber;
    }

    /**
     * @param mixed $generatedCadNumber
     */
    public function setGeneratedCadNumber($generatedCadNumber)
    {
        $this->generatedCadNumber = $generatedCadNumber;
    }

    /**
     * @return mixed
     */
    public function getTvcDeliveryCode()
    {
        return $this->tvcDeliveryCode;
    }

    /**
     * @param mixed $tvcDeliveryCode
     */
    public function setTvcDeliveryCode($tvcDeliveryCode)
    {
        $this->tvcDeliveryCode = $tvcDeliveryCode;
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
        $this->lateFee = Helpers::convertVariableToBool($lateFee);
    }

    /**
     * @return mixed
     */
    public function getProductCategoryCode()
    {
        return $this->productCategoryCode;
    }

    /**
     * @param mixed $productCategoryCode
     */
    public function setProductCategoryCode($productCategoryCode)
    {
        $this->productCategoryCode = $productCategoryCode;
    }

    /**
     * @return mixed
     */
    public function getOriginalTvcId()
    {
        return $this->originalTvcId;
    }

    /**
     * @param mixed $originalTvcId
     */
    public function setOriginalTvcId($originalTvcId)
    {
        $this->originalTvcId = $originalTvcId;
    }

    /**
     * @return mixed
     */
    public function getOriginalKeyNumber()
    {
        return $this->originalKeyNumber;
    }

    /**
     * @param mixed $originalKeyNumber
     */
    public function setOriginalKeyNumber($originalKeyNumber)
    {
        $this->originalKeyNumber = $originalKeyNumber;
    }

    /**
     * @return mixed
     */
    public function getTvcInvoiceId()
    {
        return $this->tvcInvoiceId;
    }

    /**
     * @param $invoiceId
     */
    public function setTvcInvoiceId($invoiceId)
    {
        $this->tvcInvoiceId = $invoiceId;
    }

    /**
     * @return mixed
     */
    public function getOriginalJobId()
    {
        return $this->originalJobId;
    }

    /**
     * @param mixed $originalJobId
     */
    public function setOriginalJobId($originalJobId)
    {
        $this->originalJobId = $originalJobId;
    }

    /**
     *
     * @param bool $isOverride
     */
    public function getIsOverride() {
        return Helpers::convertVariableToBool($this->isOverride);
    }

    /**
     *
     * @param bool $isOverride
     */
    public function setIsOverride($isOverride) {
        $this->isOverride = $isOverride;
    }

    /**
     *
     * @param integer $transactionId
     */
    public function getTransactionId() {
        return $this->transactionId;
    }

    /**
     *
     * @param integer $transactionId
     */
    public function setTransactionId($transactionId) {
        $this->transactionId = $transactionId;
    }

    /**
     * @return mixed
     */
    public function getTvcToAir()
    {
        return $this->tvcToAir;
    }

    /**
     * @param mixed $tvcToAir
     */
    public function setTvcToAir($tvcToAir)
    {
        $this->tvcToAir = $tvcToAir;
    }

    /**
     * @return mixed
     */
    public function getTvcType()
    {
        return $this->tvcType;
    }

    /**
     * @param mixed $tvcType
     */
    public function setTvcType($tvcType)
    {
        $this->tvcType = $tvcType;
    }

    /**
     * @return mixed
     */
    public function getWithdrawnDate()
    {
        return $this->withdrawnDate;
    }

    /**
     * @param $withdrawnDate
     */
    public function setWithdrawnDate($withdrawnDate)
    {
        if(!empty($withdrawnDate)) {
            $withdrawnDate = new \DateTime($withdrawnDate);
            $this->withdrawnDate = $withdrawnDate->format('Y-m-d H:i:s');
        }
    }

    /**
     * @return mixed
     */
    public function getTvcDAREvent()
    {
        return $this->tvcDAREvent;
    }

    /**
     * @param mixed $tvcDAREvent
     */
    public function setTvcDAREvent($tvcDAREvent)
    {
        $this->tvcDAREvent = $tvcDAREvent;
    }

    /**
     * @return mixed
     */
    public function getTvcDAREventDate()
    {
        return $this->tvcDAREventDate;
    }

    /**
     * @param mixed $tvcDAREventDate
     */
    public function setTvcDAREventDate($tvcDAREventDate)
    {
        $this->tvcDAREventDate = $tvcDAREventDate;
    }

    /**
     * @return mixed
     */
    public function getTvcTotalCharge()
    {
        return $this->tvcTotalCharge;
    }

    /**
     * @param mixed $tvcTotalCharge
     */
    public function setTvcTotalCharge($tvcTotalCharge)
    {
        $this->tvcTotalCharge = $tvcTotalCharge;
    }

    /**
     * @return mixed
     */
    public function getTvcChargeExGST()
    {
        return $this->tvcChargeExGST;
    }

    /**
     * @param mixed $tvcChargeExGST
     */
    public function setTvcChargeExGST($tvcChargeExGST)
    {
        $this->tvcChargeExGST = $tvcChargeExGST;
    }

    /**
     * @return mixed
     */
    public function getTvcChargeGST()
    {
        return $this->tvcChargeGST;
    }

    /**
     * @param mixed $tvcChargeGST
     */
    public function setTvcChargeGST($tvcChargeGST)
    {
        $this->tvcChargeGST = $tvcChargeGST;
    }

}
