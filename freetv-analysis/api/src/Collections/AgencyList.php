<?php
/**
 * Created by PhpStorm.
 * User: jeremy.paul
 * Date: 8/09/2015
 * Time: 10:02 AM
 */

namespace App\Collections;
use App\Utility\Helpers;
use \Elf\Db\AbstractCollection;
use Elf\Exception\NotFoundException;
use Elf\Http\Request;
use Elf\Utility\Convert;


class AgencyList extends AbstractCollection {
    private $sqlFilters;
    private $autocompleteRestriction = false;

    private $filterList = array(
        "agencyCode" => array (
            'table_column_name' => 'agencyCode',
        ),
        "agencyName" => array (
            'table_column_name' => 'agencyName',
        ),
        "agencyGroupId" => array (
            'table_column_name' => 'agr_id',
        ),
        "billingSubGroup" => array (
            'table_column_name' => 'billingSubGroup',
        ),
        "agencyApproved" => array (
            'table_column_name' => 'agencyApproved',
            'type' => 'boolean',
        ),
        "billingCode" => array (
            'table_column_name' => 'billingCode',
        ),
        "creditLimitMinimum" => array (
            'table_column_name' => 'creditLimit',
        ),
        "creditLimitMaximum" => array (
            'table_column_name' => 'creditLimit',
        ),
        "stopCreditId" => array (
            'table_column_name' => 'scr_id',
        ),
        "accountType" => array (
            'table_column_name' => 'accountType',
        ),
        "purchaseOrder" => array (
            'table_column_name' => 'purchaseOrder',
        ),
        "overseasGSTStatusApproved" => array (
            'table_column_name' => 'overseasGSTStatusApproved',
            'type' => 'boolean',
        ),
        "active" => array (
            'table_column_name' => 'active',
            'type' => 'boolean',
        ),
        "country" => array (
            'table_column_name' => 'country',
        ),
        "state" => array (
            'table_column_name' => 'state',
        ),
        "lastApplicationDateFrom" => array (
            'table_column_name' => 'lastApplicationDate',
        ),
        "lastApplicationDateTo" => array (
            'table_column_name' => 'lastApplicationDate',
        ),
        "lastSubmittedJobId" => array (
            'table_column_name' => 'lastSubmittedJobId',
        ),
    );

    public function setParameters(Request $request) {
        $filters = $this->filterList;
        $restrictions = $request->query('restrict');
        if($restrictions == 'autocomplete') {
            $this->autocompleteRestriction = true;
            $agencyName = $request->query('agencyName');
            $this->sqlFilters .= " ag_name LIKE '%" . urldecode($agencyName) . "%'";
            // Return early since we're only going to search on the name
            return;
        }
        foreach($filters as $filter => $parameter) {
            if(isset($parameter['type']) && $parameter['type'] = 'boolean' ) {
                $value = $request->query($filter);
                if (!empty($value) || $value === 0 || $value == '0') {
                    $value = Convert::toBoolean($request->query($filter));
                    if (!$value) {
                        $this->sqlFilters .= ' AND (' . $parameter['table_column_name'] . "='" . $value . "' OR " . $parameter['table_column_name'] . " IS NULL) ";
                    } else {
                        $this->sqlFilters .= ' AND ' . $parameter['table_column_name'] . "='" . $value . "'";
                    }
                }
            } else {

                if (($value = $request->query($filter)) && ($filter == 'lastApplicationDateFrom')) {
                    $dateFrom = new \DateTime($value);
                    $this->sqlFilters .= ' AND ' . $parameter['table_column_name'] . " >= '" . $dateFrom->format("Y-m-d") . "'";
                } elseif (($value = $request->query($filter)) && ($filter == 'lastApplicationDateTo')) {
                    $dateTo = new \DateTime($value);
                    $this->sqlFilters .= ' AND ' . $parameter['table_column_name'] . " <= '" . $dateTo->format("Y-m-d") . " 23:59:59'";
                } elseif (($value = $request->query($filter)) && ($filter == 'creditLimitMinimum')) {
                    $this->sqlFilters .= ' AND ' . $parameter['table_column_name'] . " >= '" . $value . "'";
                } elseif (($value = $request->query($filter)) && ($filter == 'creditLimitMaximum')) {
                    $this->sqlFilters .= ' AND ' . $parameter['table_column_name'] . " <= '" . $value . "'";
                } elseif ($value = $request->query($filter)) {
                    $this->sqlFilters .= ' AND ' . $parameter['table_column_name'] . " LIKE '%" . urldecode($value) . "%'";
                }
            }
        }
        if (!empty($this->sqlFilters)) {
            $this->sqlFilters = ltrim($this->sqlFilters,' AND ');
        }
    }

    public function findDuplicateAgencies ($id = null,$name = null)
    {
        $data = array();
        $sql = "SELECT
                  ag.ag_id AS agencyID,
                  ag.ag_code AS agencyCode,
                  ag.ag_name AS agencyName,
                  agr.agr_name as agencyGroup,
                  n.net_name as agencyNetwork
                FROM
                  agencies ag
                LEFT JOIN
                  networks n on n.net_id = ag.ag_net_id
                LEFT JOIN
                  agency_groups agr on agr.agr_id = ag.ag_agr_id";

        if(!empty($id)) {
            $params = array(
                ':src_ag_id' => $id,
                ':ag_id' => $id
            );
            $where = "WHERE
                          SOUNDEX(ag.ag_name)
                           =
                            SOUNDEX(
                             (SELECT
                                ag_name
                              FROM agencies WHERE
                                ag_id = :ag_id
                              )
                            ) AND ag.ag_id <> :src_ag_id";

            $sql = $sql. " " . $where;
            $data = $this->fetchAllAssoc($sql, $params);

        } elseif(!empty($name)) {
            $params = array(
                ':ag_name' => '%' . $name . '%',
            );

            $where = "WHERE
                  ag.ag_name LIKE :ag_name";

            $sql = $sql. " " . $where;
            $data = $this->fetchAllAssoc($sql, $params);
        }
        return $data;
    }

    public function retrieveAutocompleteList ()
    {
        $sql = "SELECT
                dbo.agencies.ag_id as agencyId,
                dbo.agencies.ag_code as agencyCode,
                dbo.agencies.ag_name as agencyName,
                null as abn,
                null as primaryContactEmail,
                null as accountsEmailAddress,
                null as phoneNumber,
                null as accountsPhoneNumber,
                null  address1,
                null  city,
                null  state,
                null  postCode,
                null  country
                FROM dbo.agencies
                WHERE {$this->sqlFilters}
                ORDER BY agencyId";
        $data = $this->fetchAllAssoc($sql);
        return $data;
    }

    public function getAgencyCollection ()
    {
        if($this->autocompleteRestriction) {
            return $this->retrieveAutocompleteList();
        }
        // @TODO remove this 100
        $sql = "SELECT TOP 1000  agency_table.*,job_table.lastApplicationDate,job_table.lastSubmittedJobId FROM
                (
                    (
                    SELECT
                            dbo.agencies.ag_id as agencyId,
                            dbo.agencies.ag_code as agencyCode,
                            dbo.agencies.ag_name as agencyName,
                            dbo.agencies.ag_country as country,
                            dbo.agencies.ag_sta_id as state,
                            agencyGroup.agr_id ,
                            agencyGroup.agr_name ,
                            dbo.agencies.ag_billing_sub_group as billingSubGroup,
                            dbo.agencies.ag_is_approved as agencyApproved,
                            dbo.agencies.ag_abn as abn,
                            dbo.agencies.ag_billing_code as billingCode,
                            dbo.agencies.ag_credit_limit as creditLimit,
                            stopCredits.scr_id ,
                            stopCredits.scr_number ,
                            stopCredits.scr_reason, 
                            dbo.agencies.ag_account_type as accountType,
                            dbo.agencies.ag_purchase_order_required as purchaseOrder,
                            dbo.agencies.ag_overseas_gst as overseasGSTStatusApproved,
                            dbo.agencies.ag_is_active as active,
                            networkTable.net_id,
                            networkTable.net_code,
                            networkTable.net_name
                        FROM dbo.[agencies]
                        LEFT JOIN dbo.agency_groups as agencyGroup
                        ON dbo.[agencies].ag_agr_id = agencyGroup.agr_id

                        LEFT JOIN dbo.networks as networkTable
                        ON dbo.[agencies].ag_net_id = networkTable.net_id

                        LEFT JOIN dbo.stop_credits as stopCredits
                        ON dbo.[agencies].ag_scr_id = stopCredits.scr_id
                    ) as agency_table
                    LEFT JOIN
                    (
                        SELECT
                            dbo.jobs.job_ag_id AS job_agency_id,
                            MAX(jobs.job_id) AS lastSubmittedJobId,
                            MAX(dbo.jobs.job_submission_date) AS lastApplicationDate
                        FROM
                            dbo.jobs
                        GROUP BY
                            dbo.jobs.job_ag_id
                    ) as job_table
                    ON job_table.job_agency_id = agency_table.agencyId
                    AND (agency_table.active != 1 OR agency_table.active IS NULL  )
                ) ";
        if (!empty($this->sqlFilters)) {
            $sql .=  'WHERE ' . $this->sqlFilters . " ORDER BY agencyId";
        } else {
            $sql .= "  ORDER BY agencyId";
        }
        $data = $this->fetchAllAssoc($sql);
        foreach ($data as $agency => $parameters) {
            $data[$agency]['agencyGroup'] = array (
                                        'id' => $data[$agency]['agr_id'],
                                        'name' => $data[$agency]['agr_name'],
                                    );
            $data[$agency]['stopCredit'] =  array (
                                        'id' => $data[$agency]["scr_id"],
                                        'number' => $data[$agency]["scr_number"],
                                        'reason' => $data[$agency]["scr_reason"],
                                    );
            $data[$agency]['networkDetails'] =  array (
                                            'id' => $data[$agency]["net_id"],
                                            'code' => $data[$agency]["net_code"],
                                            'name' => $data[$agency]["net_name"],
                                        );
            unset($data[$agency]["net_id"],$data[$agency]["net_code"],$data[$agency]["net_name"],$data[$agency]['agr_name'],$data[$agency]['agr_id'],$data[$agency]['scr_id'],$data[$agency]['scr_number'],$data[$agency]['scr_reason']);
        }
        if(empty($data)) {
            throw new NotFoundException("No results");
        }

        return $data;
    }
    public function fetch()
    {

    }

    public function setParams($params = array())
    {

    }


}
