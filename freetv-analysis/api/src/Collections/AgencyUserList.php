<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Collections;
use \Elf\Db\AbstractCollection;
use Elf\Exception\NotFoundException;
use \Elf\Utility\Convert;

/**
 * Description of Rolelist
 *
 * @author michael
 */
class AgencyUserList extends AbstractCollection 
{

    //put your code here
    public function getAllAgencyUsers($agencyId = null)     //dont think this is needed
    {
        $params = array();
        
        $sql1 = "SELECT "
                . "agu_sysid as userSysid, "
                . "agu_id as userId, "
                . "agu_ag_id as agencyId, "
                . "agu_email_address as email, "
                . "agu_first_name as firstName, "
                . "agu_last_name as lastName,"
                . "agu_telephone_area_code as areaCodePhone,"
                . "agu_telephone as phone,"
                . "agu_mobile as mobile,"
                . "agu_fax_area_code as areaCodeFax,"
                . "agu_fax as fax,"
                . "agu_is_network_manager as isNetworkManager, "
                . "agu_is_network_user as isNetworkUser, "
                . "agu_is_active as isActive, "
                . "agu_is_agency_admin as isAgencyAdmin, "
                . "ag_code as agencyCode "
                . "FROM dbo.agency_users "
                . "LEFT JOIN dbo.agencies "
                . "ON agencies.ag_id = agu_ag_id ";
        $sql1 .= "WHERE agu_is_network_user = 0 AND agu_is_network_manager = 0";     //for legacy system as network users were agency users
		
		 $sql2 = "SELECT "
                . "agu_sysid as userSysid, "
                . "agu_id as userId, "
                . "agu_ag_id as agencyId, "
                . "agu_email_address as email, "
                . "agu_first_name as firstName, "
                . "agu_last_name as lastName,"
                . "agu_telephone_area_code as areaCodePhone,"
                . "agu_telephone as phone,"
                . "agu_mobile as mobile,"
                . "agu_fax_area_code as areaCodeFax,"
                . "agu_fax as fax,"
                . "agu_is_network_manager as isNetworkManager, "
                . "agu_is_network_user as isNetworkUser, "
                . "agu_is_active as isActive, "
                . "agu_is_agency_admin as isAgencyAdmin, "
                . "ag_code as agencyCode "
                . "FROM dbo.agency_users agu "
                . "LEFT JOIN dbo.agency_agency_user aau "
                . "on aau.aau_agu_id = agu.agu_id "
				. "LEFT JOIN dbo.agencies ag "
                . "ON ag.ag_id = agu_ag_id ";
				;
        $sql2 .= "WHERE agu_is_network_user = 0 AND agu_is_network_manager = 0";  
		
		
        if(null !== $agencyId) { // add the agency id if specified
            $params[':agu_ag_id1'] = $agencyId;
            $params[':agu_ag_id2'] = $agencyId;
            $sql1 .= " AND agu_ag_id = :agu_ag_id1";
            $sql2 .= " AND aau_ag_id = :agu_ag_id2";
        }
		
		$sql = "(".$sql1.")"."UNION". "(".$sql2.")";
		
        $data = $this->fetchAllAssoc($sql, $params);

        if ($data === false) {
            throw new NotFoundException("No users for this agency");
        }
        foreach($data as &$datum) {
            $datum['isNetworkManager'] = Convert::toBoolean($datum['isNetworkManager']);
            $datum['isNetworkUser'] = Convert::toBoolean($datum['isNetworkUser']);
            $datum['isActive'] = Convert::toBoolean($datum['isActive']);
            $datum['isAgencyAdmin'] = Convert::toBoolean($datum['isAgencyAdmin']);
        }

        
        if(empty($data)) {
            throw new \Exception("Not Found");
        }
       
        return $data;
    }

    public function fetch() {
        
    }

    public function setParams($params = array()) {
        
    }
}
