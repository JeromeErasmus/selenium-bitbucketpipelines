<?php

namespace App\Models;

class XeroFiles extends \Elf\Db\AbstractAction {


    public function load(){}
    public function save(){}

    /**
     * Retrieve's all customer data that should appear on the Xero report
     *
     * @return bool
     */
    public function getCustomerData()
    {
        $sql = "SELECT
                  ag.ag_id,
                  ag.ag_name AS ContactName,                                                  -- *ContactName
                  ag.ag_billing_code AS AccountNumber,                                        -- AccountNumber
                  ag.ag_accounts_email AS EmailAddress,                                       -- EmailAddress
                  'Accounts Payable' AS POAttentionTo,                                        -- POAttentionTo
                  ag.ag_address1 AS POAddressLine1,                                           -- POAddressLine1
                  ag.ag_city AS POCity,                                                       -- POCity
                  s.sta_name AS PORegion,                                                     -- PORegion
                  ag.ag_postcode AS POPostalCode,                                             -- POPostalCode
                  'Australia' AS POCountry,                                                   -- POCountry
                  'Accounts Payable' AS SAAttentionTo,                                        -- SAAttentionTo
                  ag.ag_address1 AS SAAddressLine1,                                           -- SAAddressLine1
                  ag.ag_city AS SACity,                                                       -- SACity
                  s.sta_name AS SARegion,                                                     -- SARegion
                  ag.ag_postcode AS SAPostalCode,                                             -- SAPostalCode
                  'Australia' AS SACountry,                                                   -- SA Country
                  ag.ag_accounts_phone_number AS PhoneNumber,                                 -- PhoneNumber
                  'GST on Income' AS AccountsReceivableTaxCodeName,                           -- AccountsReceivableTaxCodeName
                  30 AS DueDateSalesDay,                                                      -- DueDateSalesDay
                  'Following Month' AS DueDateSalesTerm,                                      -- DueDateSalesTerm
                  5004 AS SalesAccount,                                                       -- SalesAccount
                  'Exclusive' AS DefaultTaxSales                                              -- DefaultTaxSales
                FROM agencies ag
                  LEFT JOIN states s ON s.sta_id = ag.ag_state
                WHERE ag.ag_is_xero_sync_update <> :is_sync_update  -- Check if it was already exported
                  AND ag.ag_account_type = :account_type
                  AND ag.ag_billing_code = ag.ag_code -- Get only the `parent` billing code
                  AND ag.ag_billing_code NOT IN ('4MAT70FD', 'ACCO0FEB', 'AMEX124D', 'OVER682C') -- Filter test agencies
                ORDER BY
                  ag.ag_billing_code,
                  ag.ag_name,
                  ag.ag_address1,
                  ag.ag_address2,
                  ag.ag_city,
                  ag.ag_postcode,
                  ag.ag_corp_affairs_no,
                  ag.ag_credit_limit,
                  ag.ag_account_type";

        $params = [
            ':is_sync_update' => 1,                     // Contacts that hasn't been synchronized
            ':account_type' => AGENCY_ACCOUNT_TYPE_ACC  // Only account contacts
        ];

        $data = $this->fetchAllAssoc($sql, $params);

		if ( empty($data) ) {
			return;
		}

        // Get all agency IDs to update ag_is_sync_update value
        $updateParams = implode(', ', array_column($data, 'ag_id'));

        $sql = "UPDATE agencies SET ag_is_xero_sync_update = 1 WHERE ag_id IN ({$updateParams})";
        
        $this->execute($sql);

        // Remove ag_id key as we don't need it for the CSV file
        $data = $this->removeAgencyIdColumn($data);

        return $data;
    }


    /**
     * Retrieves all the invoices that were issued in the provided dates
     * for output to the Xero report
     * @param $startDate
     * @param $endDate
     * @return mixed
     */
    public function getInvoiceData($startDate, $endDate)
    {
        // Start date
        $startDate = new \DateTime($startDate);
        $startDate->setTime(00, 00, 00);

        // End date
        $endDate= new \DateTime($endDate);
        $endDate->setTime(23, 59, 59);
        $params[':endDate'] = $endDate->format("Y-m-d H:i:s");

        // Query parameters
        $params = [
            ':startDate' => $startDate->format("Y-m-d H:i:s"),
            ':endDate' => $endDate->format("Y-m-d H:i:s"),
            ':accountType' => AGENCY_ACCOUNT_TYPE_ACC   // Only account contacts
        ];

        $sql = "SELECT
                  ag2.ag_name AS ContactName,                                                     -- *ContactName
                  i.inv_id AS InvoiceNumber,                                                      -- *InvoiceNumber
                  j.job_purchase_order AS Reference,                                              -- Reference
                  CONVERT(varchar(10), i.created_at, 3) AS InvoiceDate,                           -- *InvoiceDate
                  CONVERT(varchar(10), EOMONTH(DATEADD(month, 1, i.created_at)), 3) AS DueDate,   -- *DueDate
                  i.inv_job_id AS Description,                                                    -- *Description
                  1 AS Quantity,                                                                  -- *Quantity
                  i.inv_amount_inc_gst AS UnitAmount,                                             -- *UnitAmount
                  5004 AS AccountCode,                                                            -- *AccountCode
                  'GST on Income' AS TaxType                                                      -- *TaxType
                FROM
                  invoices i
                  JOIN jobs j ON i.inv_job_id = j.job_id
                  LEFT JOIN tvcs t ON t.tvc_id = (
                      SELECT TOP 1 tvc_id FROM dbo.tvcs
                      WHERE tvc_job_id = j.job_id
                    )
                  JOIN agencies ag ON ag.ag_id = j.job_ag_id
                  JOIN agencies ag2 ON ag.ag_billing_code = ag2.ag_code -- Join to get the name of the `parent` agency
                WHERE i.created_at >= :startDate
                  AND i.created_at <= :endDate
                  AND ag.ag_account_type = :accountType";

        $data = $this->fetchAllAssoc($sql, $params);

        return $data;
    }

    /**
     * Remove agency ID column from array
     * @param $data
     * @return mixed
     */
    private function removeAgencyIdColumn($data)
    {
        return $this->removeKeyFromArray('ag_id', $data);
    }

    /**
     * Remove key in array
     * @param $removedKey
     * @param $data
     * @return mixed
     */
    private function removeKeyFromArray($removedKey, $data)
    {
        foreach ( $data as $key => $value ) {
            if ( isset($data[$key][$removedKey]) ) {
                unset($data[$key][$removedKey]);
            }
        }

        return $data;
    }
}