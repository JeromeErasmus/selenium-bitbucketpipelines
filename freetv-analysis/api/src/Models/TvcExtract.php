<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Models;

use Elf\Exception\NotFoundException;

/**
 * Description of TvcExtract
 *
 * @author luca.confalonieri
 */
class TvcExtract extends \Elf\Db\AbstractAction
{

    private $days;


    protected $fieldMap = array(
        'tvc_key_no' => array (
            'type' => 'string',
            'displayName' => 'KeyNumber',
            'required' => false,
            'allowEmpty' => true,
        ),
        'job_reference_no' => array (
            'type' => 'string',
            'displayName' => 'CADReference',
            'required' => false,
            'allowEmpty' => true,
        ),
        'adv_code' => array (
            'type' => 'string',
            'displayName' => 'AdvertCode',
            'required' => false,
            'allowEmpty' => true,
        ),
        'adv_name' => array (
            'type' => 'string',
            'displayName' => 'AdvertName',
            'required' => false,
            'allowEmpty' => true,
        ),
        'ag_code' => array (
            'type' => 'string',
            'displayName' => 'AgencyCode',
            'required' => false,
            'allowEmpty' => true,
        ),
        'ag_name' => array (
            'type' => 'string',
            'displayName' => 'AgencyName',
            'required' => false,
            'allowEmpty' => true,
        ),
        'ag_address1' => array (
            'type' => 'string',
            'displayName' => 'AgencyAddress1',
            'required' => false,
            'allowEmpty' => true,
        ),
        'ag_address2' => array (
            'type' => 'string',
            'displayName' => 'AgencyAddress2',
            'required' => false,
            'allowEmpty' => true,
        ),
        'ag_city' => array (
            'type' => 'string',
            'displayName' => 'AgencyCity',
            'required' => false,
            'allowEmpty' => true,
        ),
        'sta_name' => array (
            'type' => 'string',
            'displayName' => 'AgencyState',
            'required' => false,
            'allowEmpty' => true,
        ),
        'ag_postcode' => array (
            'type' => 'string',
            'displayName' => 'AgencyPostCode',
            'required' => false,
            'allowEmpty' => true,
        ),
        'ag_phone_number' => array (
            'type' => 'string',
            'displayName' => 'AgencyPhone',
            'required' => false,
            'allowEmpty' => true,
        ),
        'ag_fax_number' => array (
            'type' => 'string',
            'displayName' => 'AgencyFax',
            'required' => false,
            'allowEmpty' => true,
        ),
        'tvc_arrival_date' => array (
            'type' => 'string',
            'displayName' => 'ArrivalDate',
            'required' => false,
            'allowEmpty' => true,
        ),
        'tvc_cad_assigned_date' => array (
            'type' => 'string',
            'displayName' => 'AssignedDate',
            'required' => false,
            'allowEmpty' => true,
        ),
        'advice_number' => array (
            'type' => 'string',
            'displayName' => 'AdviceNumber',
            'required' => false,
            'allowEmpty' => true,
        ),
        'tvc_product_description' => array (
            'type' => 'string',
            'displayName' => 'ProductDescription',
            'required' => false,
            'allowEmpty' => true,
        ),
        'tvc_length' => array (
            'type' => 'string',
            'displayName' => 'Length',
            'required' => false,
            'allowEmpty' => true,
        ),
        'tvc_classification_code' => array (
            'type' => 'string',
            'displayName' => 'Classification',
            'required' => false,
            'allowEmpty' => true,
        ),
        'tvc_expiry_date' => array (
            'type' => 'string',
            'displayName' => 'ExpireyDate',
            'required' => false,
            'allowEmpty' => true,
        ),
        'timestamp' => array (
            'type' => 'string',
            'displayName' => 'Tstamp',
            'required' => false,
            'allowEmpty' => true,
        ),

    );

    public function load()
    {
        $dates = $this->prepareDatesFromDays();

        $sql = "SELECT 
                    t.tvc_key_no,
                    j.job_reference_no,
                    adv.adv_code,
                    adv.adv_name,
                    ag.ag_code,
                    ag.ag_name,
                    ag.ag_address1,
                    ag.ag_address2,
                    ag.ag_city,
                    st.sta_name,
                    ag.ag_postcode,
                    {fn CONCAT(ag.ag_area_code , ag_phone)} as ag_phone_number,
                    {fn CONCAT(ag.ag_area_code , ag_fax)} as ag_fax_number,
                    CONVERT(VARCHAR ,t.tvc_arrival_date,3) as tvc_arrival_date,
                    CONVERT(VARCHAR,t.tvc_cad_assigned_date,3) as tvc_cad_assigned_date,
                    t.tvc_cad_no as advice_number,
                    t.tvc_product_description,
                    t.tvc_length,
                    t.tvc_classification_code,
                    CONVERT(VARCHAR,t.tvc_expiry_date,3) as tvc_expiry_date
                    FROM 
                    tvcs t
                    LEFT JOIN jobs j ON j.job_id = t.tvc_job_id 
                    LEFT JOIN advertisers adv ON adv.adv_id = j.job_adv_id 
                    LEFT JOIN agencies ag ON ag.ag_id = j.job_ag_id
                    LEFT JOIN states st ON st.sta_id = ag_sta_id
                    WHERE 
                    (j.job_last_amend_date  BETWEEN :last_amend_date_1 AND :last_amend_date_2
                    OR 
                    j.job_submission_date  BETWEEN :job_submission_date_1 AND :job_submission_date_2 
                    OR 
                    t.tvc_cad_assigned_date  BETWEEN :tvc_cad_assigned_date_1 AND :tvc_cad_assigned_date_2)
                    AND j.job_jty_id <> :pre_check_id
                    AND t.tvc_cad_no IS NOT NULL AND t.tvc_cad_no <> ''
                    AND t.tvc_event_type <> :withdraw_event_type
                    AND t.tvc_event_type <> :rejected_event_type
                ";

        $jobTypes = $this->app->config->get('jobTypes');

        $timeStamp = new \DateTime();
        $timeStamp = $timeStamp->format('h:i:s A d/m/Y');

        $params = array (
            ':last_amend_date_1' => $dates['lessRecent'],
            ':last_amend_date_2' => $dates['mostRecent'],
            ':job_submission_date_1' => $dates['lessRecent'],
            ':job_submission_date_2' => $dates['mostRecent'],
            ':tvc_cad_assigned_date_1' => $dates['lessRecent'],
            ':tvc_cad_assigned_date_2' => $dates['mostRecent'],
            ':pre_check_id' => $jobTypes['precheck'],
            ':withdraw_event_type' => $this->app->config->withdrawnEventType,
            ':rejected_event_type' => $this->app->config->rejectedEventType
        );

        $dataArray = $this->fetchAllAssoc($sql, $params);

        if(!empty($dataArray)){
            foreach ($dataArray as $key => $data) {
                $data['timestamp'] = $timeStamp;
                $data['tvc_length'] = round($data['tvc_length']);
                $data['tvc_product_description'] = preg_replace("/\r\n|\n/", " ", $dataArray[$key]['tvc_product_description']);
                $dataArray[$key] = $data;
            }
            return $dataArray;
        }
        throw new NotFoundException(['displayMessage' => 'No results found']);
    }

    public function save(){}

    public function headers($dataArray)
    {
        $headers = array();

        foreach($dataArray[0] as $key => $data){
            if(isset($this->fieldMap[$key]['displayName'])){
                $headers[] = $this->fieldMap[$key]['displayName'];
            }else{
                $headers[] = $key;
            }
        }
        return $headers;
    }

    /**
     * from a number of days get two dates for the period of time is specified as 'number of days' and excludes today.
     * For example if today is Friday and the specified period is 3 days, the TVC extract will include jobs that have had activity on Thursday, Wednesday and Tuesday.
     * @return array dates
     */
    private function prepareDatesFromDays()
    {

        // need to count from yesterday n days back.
        $date = new \DateTime();
        $daysBack = $this->days;
        $mostRecent = $date->format('Y-m-d H:i:s');
        $date->sub(new \DateInterval('P'.$daysBack.'D'));
        $date->setTime(0,0,0);
        $lessRecent = $date->format('Y-m-d H:i:s');

        return array(
            'mostRecent' => $mostRecent,
            'lessRecent' => $lessRecent
        );
    }


    public function setDays($days)
    {
        $this->days = $days;
    }

}
