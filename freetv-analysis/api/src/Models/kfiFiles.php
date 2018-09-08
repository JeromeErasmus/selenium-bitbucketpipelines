<?php

namespace App\Models;

class kfiFiles extends \Elf\Db\AbstractAction {


    public function load(){}
    public function save(){}

    /**
     * Retrieve's all customer data that should appear on the cadcus report
     *
     * @return bool
     */
    public function getCustomerData(){

        $sql = "SELECT
          ag_id,
          ag_billing_code,
          ag_name,
          COALESCE(ag_address1, '') + ' ' + COALESCE(ag_address2, '') AS concatenatedAddress,
          ag_city,
          s.sta_name,
          ag_postcode,
          ag_corp_affairs_no,
          ag_credit_limit,
          ag_account_type
        FROM
          agencies ag
          LEFT JOIN states s
            ON s.sta_id = ag.ag_sta_id
        WHERE
            ag_is_sync_update <> 1
            AND ag_account_type = 'ACC'
        ORDER BY
            ag_billing_code,
          ag_name,
          ag_address1,
          ag_address2,
          ag_city,
          s.sta_name,
          ag_postcode,
          ag_corp_affairs_no,
          ag_credit_limit,
          ag_account_type;
        ";

        $data = $this->fetchAllAssoc($sql);

		if(empty($data)){
			return;
		}
        $updateParams = implode(', ', array_column($data, 'ag_id'));

        $sql = "UPDATE agencies set ag_is_sync_update = 1 WHERE ag_id in  ({$updateParams})";
        
        $this->execute($sql);

        return $data;
    }


    /**
     * Retrieves all the invoices that were issued in the provided dates
     * for output to the cadinv report
     *
     * @return bool
     */
    public function getInvoiceData($startDate, $endDate){


        $startDate= new \DateTime($startDate);
        $startDate->setTime(00, 00, 00);
        $params[':startDate'] = $startDate->format("Y-m-d H:i:s");

        $endDate= new \DateTime($endDate);
        $endDate->setTime(23, 59, 59);
        $params[':endDate'] = $endDate->format("Y-m-d H:i:s");

        $sql = "
        SELECT
          REPLACE(
            CONVERT(VARCHAR (10), i.created_at, 3),
            '/',
            ''
          ) AS date_created, -- ddmmyy year format
          ag.ag_overseas_gst,
          ag.ag_billing_code,
          j.job_reference_no,
          j.job_purchase_order,
          i.inv_id,
          i.inv_amount_inc_gst,
          t.tvc_key_no
        FROM
          invoices i
          JOIN jobs j
            ON i.inv_job_id = j.job_id
          LEFT JOIN tvcs t 
            ON t.tvc_id = (
              SELECT TOP 1 tvc_id FROM dbo.tvcs
              WHERE tvc_job_id = j.job_id
            )
          JOIN agencies ag
            ON ag.ag_id = j.job_ag_id
        WHERE i.created_at >= :startDate
          AND i.created_at <= :endDate
          AND ag_account_type = 'ACC'
        ";

        $data = $this->fetchAllAssoc($sql, $params);

        return $data;
    }
}