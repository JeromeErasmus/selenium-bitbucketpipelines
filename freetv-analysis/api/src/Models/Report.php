<?php
/**
 * Created by PhpStorm.
 * User: jeremy.paul
 * Date: 7/09/2015
 * Time: 5:09 PM
 */

namespace App\Models;
use Elf\Exception\ConflictException;
use Elf\Exception\NotFoundException;
use Elf\Utility\Convert;
use App\Models\Agencies as Model;
use Illuminate\Database\Eloquent\Collection;


class Report extends \Elf\Db\AbstractAction {

    protected $dailyActivityReportFieldMap = array(
        'tvc_key_no' => array (
            'displayName' => 'Key Number',
        ),
        'tvc_event_type' => array (
            'displayName' => 'Event Type',
        ),
        'adv_name' => array (
            'displayName' => 'Advertiser',
        ),
        'ag_code' => array (
            'displayName' => 'Agency Code',
        ),
        'job_submission_date' => array (
            'displayName' => 'Job Submission Date',
        ),
        'tvc_product_description' => array (
            'displayName' => 'Description',
        ),
        'job_owner' => array (
            'displayName' => 'Job Owner',
        ),
        'job_assigned' => array (
            'displayName' => 'Job Assigned',
        ),
        'tvc_cad_no' => array (
            'displayName' => 'CAD Number',
        ),
        'tvc_length' => array (
            'displayName' => 'Duration',
        ),
        'req_station_notes' => array (
            'displayName' => 'Notes To Network',
        )
    );

    protected $transactionReportFieldHeaders = array(
        'Date/Time',
        'Invoice Number',
        'Purchase Order Number',
        'Customer Code',
        'Charity',
        'Reference Number',
        'Westpac Transaction ID',
        'Amount',
        'GST',
        'Total',
        'Exception',
    );

    public function load()
    {
        // TODO: Implement load() method.
    }

    public function save()
    {
        // TODO: Implement save() method.
    }

    public function getKFIReport($params)
    {
        unset($params['reportType']);

        $kfiGeneration = $this->app->service('kfiGeneration');
        $documentUpload= $this->app->service('documentUpload');

        if(empty($startDate)){
            $startDate = date('Y-m-d', strtotime($params['startDate']));
        }

        if(empty($endDate)){
            $endDate = date('Y-m-d', strtotime($params['endDate']));
        }

        $fileLocation = $kfiGeneration->createKFIReports($startDate, $endDate);

        return $documentUpload->documentSendFile(array('jum_system_filename' => $fileLocation), true);
    }

    /**
     * Get Xero report
     * @param $params
     * @return mixed
     */
    public function getXeroReport($params)
    {
        unset($params['reportType']);

        $xeroGeneration = $this->app->service('XeroFilesGeneration');
        $documentUpload = $this->app->service('documentUpload');

        if ( empty($startDate) ) {
            $startDate = date('Y-m-d', strtotime($params['startDate']));
        }

        if ( empty($endDate) ) {
            $endDate = date('Y-m-d', strtotime($params['endDate']));
        }

        $fileLocation = $xeroGeneration->createXeroReports($startDate, $endDate);

        return $documentUpload->documentSendFile(array('jum_system_filename' => $fileLocation), true);
    }

    public function getAgencyListReport($params)
    {
        unset($params['reportType']);

        // Searching using TVC criteria (i.e. the product category or classification code) is very particular, if the
        // user does not select any of the search criteria that uses TVC data, the sub query should not search at all
        // in case a job has been submitted, but no TVCs have been set for that particular job.
        $tvcConditions = "job_id IN (
                          SELECT
                            tvcs.tvc_job_id
                          FROM dbo.tvcs
                          WHERE 1=1 )";
        // A flag to indicate that the query to search using TVC data is required
        $tvcConditionsAreSet = 0;

        $whereParams = array();

        $agencyConditions = ' 1 = 1 ';
        $dateConditions = ' 1 = 1 ';

        foreach($params as $key => $parameter) {
            if (($key == 'productCategoryCode') && (!empty($parameter))) {
                $whereParams[':' . $key] = $parameter;
                $tvcConditionsAreSet = 1;
                $tvcConditions = rtrim($tvcConditions,')') . ' AND tvc_product_category_code = :' . $key . ' )';
            }
            if (($key == 'classificationCode') && (!empty($parameter))) {
                $whereParams[':' . $key] = $parameter;
                $tvcConditionsAreSet = 1;
                $tvcConditions = rtrim($tvcConditions,')') .  ' AND tvc_classification_code = :' . $key . ' )';
            }
            if (($key == 'accountType') && (!empty($parameter)))  {
                $whereParams[':' . $key] = $parameter;
                $agencyConditions .= ' AND ag_account_type = :' . $key . ' ';
            }
            if (($key == 'state') && (!empty($parameter)))  {
                $whereParams[':' . $key] = $parameter;
                $agencyConditions .= ' AND ag_sta_id = :' . $key . ' ';
            }
            if (($key == 'monthCount') && (!empty($parameter)))   {
                $whereParams[':' . $key] = $parameter;
                $dateConditions .= " AND jobs.job_submission_date > DATEADD(m, -1 * CONVERT(INT,:monthCount), GetDate()) ";
            }
        }

        if($tvcConditionsAreSet == 0) {
            $tvcConditions = ' 1 = 1 ';
        }

        $sql = "SELECT
                    ag_id as agencyId,
                    ag_code as agencyCode,
                    ag_name as agencyName,
                    ag_address1 as address1,
                    ag_address2 as address2,
                    ag_city as city,
                    ag_sta_id as state,
                    ag_postcode as postCode,
                    ag_phone as phone,
                    ag_contact_name as contactName,
                    ag_primary_contact_email as contactEmail
                FROM dbo.agencies
                WHERE
                  ag_id IN (
                    SELECT DISTINCT
                      jobs.job_ag_id
                    FROM dbo.jobs
                    WHERE
                      1 = 1
                      AND
                      ". $tvcConditions ."
                      AND
                      " . $dateConditions . "
                  )
                  AND " . $agencyConditions;

        return $this->fetchAllAssoc($sql,$whereParams);
    }

    public function getAdvertiserListReport($params)
    {
        $sqlParams = [];

        $availableParams = [
            'monthCount' => 'AND job_submission_date > DATEADD(m, -1 * CONVERT(INT,:monthCount), GetDate())',
            'productCategoryCode' => 'AND tvc_product_category_code = :productCategoryCode',
            'classificationCode' => 'AND tvc_classification_code = :classificationCode',
            'isActive' => 'AND adv_is_active = :isActive',
            'isApproved' => 'AND adv_is_approved = :isApproved'
        ];

        foreach($availableParams as $param_name => &$sql) {
            if( empty($params[$param_name]) ) {
                $sql = '';
            } else {
                $sqlParams[$param_name] = $params[$param_name];
            }
        }

        $sql = "
            SELECT TOP 10000
                adv_id as advertiserId,
                adv_code as advertiserCode,
                adv_name as advertiserName
            FROM
                advertisers
            WHERE
                adv_id in (
                  SELECT job_adv_id FROM jobs WHERE
                    1=1
                    {$availableParams['monthCount']}
                    AND job_id IN (
                    SELECT
                      tvc_job_id
                    FROM
                      tvcs
                    WHERE
                      1=1
                      {$availableParams['productCategoryCode']}
                      {$availableParams['classificationCode']}
                    )
                )
            {$availableParams['isActive']}
            {$availableParams['isApproved']}
";

        $results = $this->fetchAllAssoc($sql, $sqlParams);
        if (empty($results)) {
            throw new NotFoundException("No results");
        }
        return $results;
    }

    /**
     * This actually dosen't get a report, it's named this way to take advantage of the controller
     *
     * @return mixed
     */
    public function getDailyActivityRecipientsReport(){

        $sql = "SELECT id, email FROM daily_activity_recipients WHERE deleted <> 1 ";

        return $this->fetchAllAssoc($sql);

    }

    public function deleteDailyActivityRecipient($id){
        if (empty($id)) {
            throw new \Exception("No ID given.");
        }

        $sql = "UPDATE daily_activity_recipients SET deleted = 1 WHERE id = :id";

        return $this->delete($sql, array(':id' => $id));
    }

    public function addDailyActivityRecipients($params)
    {

        $processedParams = $this->processEmails($params);

        foreach($processedParams as $email){
            $sql = "
                    INSERT INTO daily_activity_recipients
                        (email)
                    VALUES
                    (:email)
            ";

            $sqlParams = array(
                ':email' => $email,
            );

            $id = $this->insert($sql, $sqlParams);
        }
        return true;

    }

    /**
     * This function assumes that the emails are comma separated in the following format
     * (because we lose all the whitespace anyway)
     *
     * 123 , 123
     * OR
     * 123, 123
     * OR
     * 123 ,123
     *
     * @string $unprocessedEmails
     */
    private function processEmails($unprocessedEmails){
        $unprocessedEmails = preg_replace('/\s+/', '', $unprocessedEmails);

        $processedEmails = explode(',', $unprocessedEmails);

        return $processedEmails;
    }

    public function getDailyActivityReport($params){
        $sqlParams = array();

        $sql = "SELECT TOP 10000
                    tvc.tvc_key_no,
                    tvc.tvc_dar_event as tvc_event_type,
                    adv.adv_name,
                    ag.ag_code,
                    j.job_submission_date,
                    tvc.tvc_product_description,
					u1.user_id as job_owner,
					u2.user_id as job_assigned,
                    tvc.tvc_cad_no,
                    tvc.tvc_length,
                     STUFF((
                        SELECT '\n' + CONVERT(VARCHAR(MAX),r.req_station_notes)
                        FROM requirements_tvc rtv
                        JOIN requirements r ON r.req_id = rtv.rtv_req_id
                        WHERE rtv.rtv_tvc_id = tvc.tvc_id
                            AND rtv.rtv_visible_on_daily_activity = 1
                            AND CONVERT(VARCHAR(MAX),r.req_station_notes) <> ''
                            AND tvc.tvc_dar_event = '". KeyNumber::STATUS_CLASSIFIED ."'
                        FOR XML PATH('')
                     ), 1, 1, '' ) AS req_station_notes
                FROM
                    jobs j
                JOIN
                    tvcs tvc
                        ON j.job_id = tvc.tvc_job_id
                JOIN
                    agencies ag
                        ON ag.ag_id = j.job_ag_id
                JOIN
                    advertisers adv
                        ON adv.adv_id = j.job_adv_id
		        JOIN
			        users u1
			            ON j.job_owner = u1.user_sysid
		        JOIN
		            users u2
			            ON j.job_assigned_user = u2.user_sysid
                WHERE tvc_dar_event_date BETWEEN :dateStart AND :dateEnd";

        // Set Parameter: date start
        if ( !empty($params["date_start"]) ) {
            $sqlParams['dateStart'] = date('Y-m-d', strtotime($params["date_start"])) . " 18:00";
        } else {
            $sqlParams['dateStart'] = date('Y-m-d' , strtotime('last weekday')) . " 18:00";
        }

        // Set Parameter: date end
        if ( !empty($params["date_end"]) ) {
            $sqlParams['dateEnd'] = date('Y-m-d', strtotime($params["date_end"])) . " 18:00";
        } else {
            $sqlParams['dateEnd'] = date('Y-m-d') . " 18:00";
        }

        $data['data'] = $this->fetchAllAssoc($sql, $sqlParams );
        $data['recipients'] = $this->getDailyActivityRecipientsReport();
        $data['headers'] = $this->getDailyActivityReportHeaders($data['data']);

        return $data;
    }


    /**
     * @param $dataArray
     * @return array
     */
    public function getDailyActivityReportHeaders($dataArray){

        $headers = array();

        foreach($dataArray[0] as $key => $data){

            if(isset($this->dailyActivityReportFieldMap[$key]['displayName'])){

                $headers[$key] = $this->dailyActivityReportFieldMap[$key]['displayName'];

            } else {

                $headers[$key] = $key;

            }
        }

        return $headers;
    }


    /**
     * Transaction report.
     * @param type $params
     * @return type
     */
    public function getTransactionReport($params)
    {
        $sqlParams = array();
        //set up dates so that the date range picks dates according to business days which start and end at 6pm, and all logged jobs after friday 6pm get included in the next mondays jobs. 
        $dateStart = new \DateTime($params['date_start']);
        if($dateStart->format("D")=="Sun"){
            $dateStart->modify('-1 day');
        }
        if($dateStart->format("D")=="Mon"){
            $dateStart->modify('-2 day');
        }
        $dateStart->setTime(18, 00, 00);
        $dateStart->modify('-1 day');
        $sqlParams[':date_start'] = $dateStart->format("Y-m-d H:i:s");
        $sqlParams[':cad_date_start'] = $dateStart->format("Y-m-d H:i:s");

        $dateEnd= new \DateTime($params['date_end']);
        if($dateEnd->format("D")=="Sat"){
            $dateEnd->modify('-1 day');
        }
        if($dateEnd->format("D")=="Sun"){
            $dateEnd->modify('-2 day');
        }
        $dateEnd->setTime(17, 59, 59);
        $sqlParams[':date_end'] = $dateEnd->format("Y-m-d H:i:s");
        $sqlParams[':cad_date_end'] = $dateEnd->format("Y-m-d H:i:s");

        $sql = "SELECT
                  *
                FROM
                  (SELECT
                    adv.adv_id AS advertiserId,
                    ag.ag_id AS agencyId,
                    inv.updated_at,
                    inv.inv_id,
                    j.job_purchase_order,
                    ag.ag_code AS customer_code,
                    CASE
                      WHEN adv.adv_is_charity = 1
                      THEN 'Y'
                      ELSE 'N'
                    END AS is_charity,
                    j.job_id,
                    CASE
                      WHEN t.ag_account_type = 'ACC'
                      THEN NULL
                      ELSE inv.inv_tra_id
                    END AS inv_tra_id,
                    inv.inv_amount_ex_gst,
                    inv.inv_gst,
                    inv.inv_amount_inc_gst,
                    t.summary_code,
                    it.id AS invoice_type_id,
                    it.type,
                    t.transaction_id,
                    inv.created_at
                  FROM
                    invoices inv
                    LEFT JOIN invoice_types it
                      ON it.id = inv.inv_invoice_type_id
                    LEFT JOIN transactions t
                      ON t.transaction_id = inv.inv_tra_id
                    JOIN jobs j
                      ON j.job_id = inv.inv_job_id
                    JOIN agencies ag
                      ON ag.ag_id = j.job_ag_id
                    JOIN advertisers adv
                      ON adv.adv_id = j.job_adv_id
                  WHERE inv.created_at >= :date_start
                    AND inv.created_at <= :date_end
                    AND inv.inv_jum_id IS NOT NULL
                  UNION
                  SELECT
                    adv.adv_id AS advertiserId,
                    ag.ag_id AS agencyId,
                    NULL AS updated_at,
                    NULL AS inv_id,
                    j.job_purchase_order,
                    ag.ag_code AS customer_code,
                    CASE
                      WHEN adv.adv_is_charity = 1
                      THEN 'Y'
                      ELSE 'N'
                    END AS is_charity,
                    j.job_id,
                    NULL AS inv_tra_id,
                    0 AS inv_amount_ex_gst,
                    0 AS inv_gst,
                    CASE
                      WHEN t.tvc_late_fee = 1
                      THEN (
                        cco.cco_billing_rate + (
                          cco.cco_billing_rate * cco_late_fee
                        ) / 100
                      )
                      ELSE cco.cco_billing_rate
                    END AS inv_amount_inc_gst,
                    NULL AS summary_code,
                    2 AS invoice_type_id,
                    'Override' AS TYPE,
                    NULL AS transaction_id,
                    t.tvc_cad_assigned_date AS created_at
                  FROM
                    tvcs t
                    JOIN jobs j
                      ON t.tvc_job_id = j.job_id
                    JOIN agencies ag
                      ON ag.ag_id = j.job_ag_id
                    JOIN advertisers adv
                      ON adv.adv_id = j.job_adv_id
                    JOIN charge_codes cco
                      ON cco.cco_id = t.tvc_charge_code
                  WHERE tvc_cad_assigned_date >= :cad_date_start
                    AND tvc_cad_assigned_date <= :cad_date_end
                    AND t.tvc_override = 1) AS temp
                ";

        $sql .= " WHERE 1=1 ";

        if(!empty($params['account_type'])) {
            if ($params['account_type'] == AGENCY_ACCOUNT_TYPE_COD) {
                $sql .= " AND inv_tra_id IS NOT NULL ";
            } else if ($params['account_type'] == AGENCY_ACCOUNT_TYPE_ACC) {
                $sql .= " AND inv_tra_id IS NULL ";
            }
        }

        if(!empty($params['charity'])){
            if($params['charity'] == "Yes"){
                $sqlParams[':charity'] = 'Y';
                $sql .= " AND is_charity = :charity ";
            }else if($params['charity'] == "No"){
                $sql .= " AND is_charity <> 'Y'";
            }
        }

        $sql .= "ORDER BY created_at";
        $result = $this->fetchAllAssoc($sql, $sqlParams);

        //mark exceptions
        foreach($result as $key => $record){
            $result[$key]['inv_amount_inc_gst'] = number_format((float)$result[$key]['inv_amount_inc_gst'], 2, '.', '');
            $result[$key]['exception'] = $this->exceptionColumn($record);
        }
        // if we are not doing a csv export
        if(!array_key_exists('export', $params)){

            // calculate total
            $total_amount = array_sum(array_column($result, 'inv_amount_ex_gst'));
            $total_gst = array_sum(array_column($result, 'inv_gst'));
            $total = array_sum(array_column($result, 'inv_amount_inc_gst'));

            // count number of non-zero transaction id
            $result_transaction_ids = array_column($result, 'inv_tra_id');
            $transaction_ids = array_map(function ($value) {
                if (!empty($value)) { return $value; }
            }, $result_transaction_ids);
            $total_transaction_ids = count($transaction_ids);

            // set data to return
            $result['total_amount'] = number_format($total_amount,2);
            $result['total_gst'] = number_format($total_gst,2);
            $result['total'] = number_format($total,2);
            $result['total_transaction_ids'] = $total_transaction_ids;
            return $result;

        }
        $data['data'] = $result;
        $data['headers'] = $this->transactionReportFieldHeaders;
        return $data;
    }

    /**
     * as we multiply values by 100 to ensure calculations are correct,
     * before displaying we need re-format figures
     *
     * @param $amount
     * @param $operator
     * @return string
     */
    private function formatAmount($amount, $operator)
    {
        $result = 0;
        if($amount > 0){
            $result = bcdiv($amount,$operator, $this->decimals);
        }
        return number_format($result, 2);
    }

    /**
     * Determine if each row of the transaction report meets exeptions requirements
     *      Manual Override	->  Transactions which have failed where the CAD user has done a manual override to assign CAD number/s
     *      Manual Invoice	->  Transactions which are a manual invoice
     *      Manual Adjustment	->  Transactions which have a manual adjustment
     *      Unknown             ->  Transactions which have the 'unknown' Westpac response
     *      (blank)             ->  Normal transaction
     * @param type $record
     * @return string
     */
    private function exceptionColumn($record)
    {
        $record['exception'] = '';
        if($record['type'] == 'Override'){
            $record['exception'] = "TVC Manual Override";
        }
        else if($record['invoice_type_id'] == $this->app->config->manualInvoice){
            $record['exception'] = "Manual Invoice";
        }
        else if(!empty($this->getManualAdjustmentsByTransactionId($record['transaction_id']))){
            $record['exception'] = "Manual Adjustment";
        }
        else if($record['summary_code'] == $this->app->config->westpacTransactionError){
            $record['exception'] = "Unknown";
        }
        return $record['exception'];
    }

    /**
     *
     * @param type $transactionId
     * @return type
     */
    private function getManualAdjustmentsByTransactionId($transactionId)
    {
        $manualAdjObj = new ManualAdjustment();
        $capsule = $this->app->service('eloquent')->getCapsule();
        return $manualAdjObj->processed($transactionId)->get()->toArray();
    }

    /**
     * true if there are any overriden tvcs in the transaction
     * @param type $transactionId
     * @return type
     */
    private function isOverride($transactionId)
    {
        $tvcObject = $this->app->model('KeyNumber');
        return !empty($tvcObject->getOverriddenTvcsByTransactionId($transactionId));
    }
}
