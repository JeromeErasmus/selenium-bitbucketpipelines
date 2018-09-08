<?php

/**
 *
 *
 * @author     Luca Confalonieri
 */
namespace App\Collections;

use Elf\Utility\Convert;

class Advertiserlist extends \Elf\Db\AbstractCollection {

    public $list;
    private $sqlParams;
    private $filter;
    private $leftJoin;
    private $having;


    public function __construct($app){
        parent::__construct($app);
    }

    public function fetch()
    {

        $filter = $this->filter;

        // 730 represents the number of days to determin if an advertiser is active or not.
        // $sql = "SELECT adv_id,
        //                adv_code,
        //                adv_name,
        //                adv_default_advertiser_category,
        //                adv_abn,
        //                adv_allow_late_submission,
        //                adv_is_charity,
        //                adv_charity_last_checked,
        //                adv_cad_notes,
        //                adv_acc_notes,
        //                adv_is_approved,
        //         CASE WHEN adv_id IN (SELECT
        //         DISTINCT
        //             a.adv_id
        //         FROM
        //             advertisers a
        //                 join
        //             jobs j
        //                 ON
        //                     j.job_adv_id = a.adv_id
        //         where
        //             datediff(day,j.job_create_date, CURRENT_TIMESTAMP)<= 730) THEN 1 ELSE 0
        //             END AS adv_active
        //         FROM advertisers adv WHERE {$filter}";

        $sql = "SELECT TOP 999 adv_id,
                       adv_code,
                       adv_name,
                       adv_default_advertiser_category,
                       adv_abn,
                       adv_allow_late_submission,
                       adv_is_charity,
                       adv_charity_last_checked,
                       adv_cad_notes,
                       adv_acc_notes,
                       adv_is_approved,
                       adv_is_active
                FROM advertisers adv WHERE {$filter}";

        $data = $this->fetchAllAssoc($sql, $this->sqlParams);

        if (empty($data)) {
            $this->list = $data;
            return true;
        }

        $formattedData = array();
        foreach ($data as $key => $advertiser) {
            $formattedData[$key] = array(
                'advertiserId' => $data[$key]['adv_id'],
                'advertiserCode' => $data[$key]['adv_code'],
                'advertiserName' => $data[$key]['adv_name'],
                'defaultCategory' => $data[$key]['adv_default_advertiser_category'],
                'abn' => $data[$key]['adv_abn'],
                'allowLateSubmissions' => Convert::toBoolean($data[$key]['adv_allow_late_submission']),
                'authorisedCharity' => Convert::toBoolean($data[$key]['adv_is_charity']),
                'charityLastChecked' => $data[$key]['adv_charity_last_checked'],
                'hasCADNotes' => Convert::toBoolean($data[$key]['adv_cad_notes']),
                'hasAccountNotes' => Convert::toBoolean($data[$key]['adv_acc_notes']),
                'active' => Convert::toBoolean($data[$key]['adv_is_active']),
                'approved' => Convert::toBoolean($data[$key]['adv_is_approved']),
            );
        }
        $this->list = $formattedData;

        return true;
    }

    public function setParams($params = array())
    {
        $this->filter = " 1=1 ";
        $this->leftJoin = '';
        $this->having = " 2=2 ";
        //sets all the where conditions for advertiser filtering
        if (!empty($params['advertiserCode'])){
            $params['advertiserCode'] = '%' . $params['advertiserCode'] . '%';
            $this->filter .= " AND adv.adv_code LIKE :adv_code ";
            $this->sqlParams[':adv_code'] = $params['advertiserCode'];
        }
        if (!empty($params['advertiserCodeOas'])){
            $this->filter .= " AND adv.adv_code = :adv_code_oas ";
            $this->sqlParams[':adv_code_oas'] = $params['advertiserCodeOas'];
        }
        if (!empty($params['name'])){
            $params['name'] = '%' . $params['name'] . '%';
            $this->filter .= " AND adv.adv_name LIKE :adv_name ";
            $this->sqlParams[':adv_name'] = $params['name'];
        }
        if (!empty($params['category'])){
            $this->filter .= " AND adv.adv_default_advertiser_category = :adv_category ";
            $this->sqlParams[':adv_category'] = $params['category'];
        }
        if (!empty($params['abn'])){
            $params['abn'] = '%' . $params['abn'] . '%';
            $this->filter .= " AND adv.adv_abn LIKE :adv_abn ";
            $this->sqlParams[':adv_abn'] = $params['abn'];
        }
        if (!empty($params['allowLateSubmissions'])){
            if(Convert::toBoolean($params['allowLateSubmissions']) == true){
                //$adv_late_sub = 1;
                $this->filter .= " AND adv.adv_allow_late_submission = 1 ";
            }
            else{
                //$adv_late_sub = 0;
                $this->filter .= " AND adv.adv_allow_late_submission <> 1 ";

            }
            //$this->sqlParams[':adv_late_sub'] = $adv_late_sub;
        }
        if (!empty($params['charityCheckedDateFrom'])){
            $dateFrom = new \DateTime($params['charityCheckedDateFrom']);
            $this->filter .= " AND adv_charity_last_checked >= :dateFrom ";
            $this->sqlParams[':dateFrom'] = $dateFrom->format("Y-m-d");
        }
        if (!empty($params['charityCheckedDateTo'])){
            $dateTo = new \DateTime($params['charityCheckedDateTo']);
            $this->filter .= " AND adv_charity_last_checked <= :dateTo ";
            $this->sqlParams[':dateTo'] = $dateTo->format("Y-m-d").'  23:59:59';
        }
        if (!empty($params['charity'])){
            if(Convert::toBoolean($params['charity']) == true){
                $this->filter .= " AND adv_is_charity = 1";
            }
            else{
                $this->filter .= " AND adv_is_charity <> 1";
            }
        }
        if (!empty($params['cadNotes'])){
            if(Convert::toBoolean($params['cadNotes']) == true){
                $this->filter .= " AND adv_cad_notes is not null AND datalength(adv_cad_notes) > 0";
            }
            else{
                $this->filter .= " AND adv_cad_notes is null OR datalength(adv_cad_notes) = 0";
            }
        }
        if (!empty($params['accountNotes'])){
            if(Convert::toBoolean($params['accountNotes']) == true){
                $this->filter .= " AND adv_acc_notes is not null AND datalength(adv_acc_notes) > 0";
            }
            else{
                $this->filter .= " AND adv_acc_notes is null OR datalength(adv_acc_notes) = 0";
            }
        }
        if ( !empty($params['active']) ) {
            //query that selects only active advertiser
            if (Convert::toBoolean($params['active']) == true) {
                $this->filter .= " AND adv.adv_is_active = 1";
            } elseif ($params['active'] == 'false') {
                $this->filter .= " AND adv.adv_is_active = 0";
            }
        }

        if (!empty($params['approved'])) {
            if (Convert::toBoolean($params['approved']) == true) {
                $this->filter .= " AND adv_is_approved = 1";
            } elseif ($params['approved'] == 'false') {
                $this->filter .= " AND adv_is_approved = 0";
            }
        }
    }

}
