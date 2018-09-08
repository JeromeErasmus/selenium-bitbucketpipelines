<?php
/**
 * Created by PhpStorm.
 * User: thimira.gunasekera
 * Date: 16/09/2015
 * Time: 11:40 AM
 */

namespace App\Collections;


use App\Utility\Helpers;
use Elf\Db\AbstractCollection;
use Elf\Exception\NotFoundException;
use Elf\Utility\Convert;

class NetworkUserlist extends AbstractCollection
{
    private $filters = " 1=1 ";
    private $filter_params = array();
    private $order = array('orderBy' => 'last_name', 'dir' => 'ASC');
    private $orderSql = null;
    private $users;
    public $list;

    private $fieldMap = array(
        'networkUserId' => array(
            'name' => 'id'
        ),
        'systemId' => array(
            'name' => 'user_sysid'
        ),
        'agencyId' => array(
            'name' => 'agency_id'
        ),
        'networkId' => array(
            'name' => 'net_id'
        ),
        'firstName' => array(
            'name' => 'first_name'
        ),
        'lastName' => array(
            'name' => 'last_name'
        ),
        'email' => array(
            'name' => 'email_address'
        ),
        'active' => array(
            'name' => 'active',
            'type' => 'boolean'
        )

    );


    public function setParams($params = array()){
        if(!empty($params['networkId'])) {
            $this->filters .= "AND net_id = :net_id";
            $this->filter_params[':net_id'] = $params['networkId'];
        }
    }
    
    public function setOrder($params = array()) {

        if(isset($params['orderBy']) && isset($this->fieldMap[$params['orderBy']])) {
            $this->order['orderBy'] = $this->fieldMap[$params['orderBy']]['name'];
        }
        
        if(isset($params['dir'])) {
            switch($params['dir']) {
                case 'desc' : 
                     $this->order['dir'] = "DESC";
                    break;
            }
        }
        
        $this->sortSQL = " ORDER BY {$this->order['orderBy']} {$this->order['dir']} ";
 
    }

    public function fetch()
    {
        $sql = "SELECT
            id,
            user_sysid,
            agency_id,
            net_id,
            first_name,
            last_name,
            email_address,
            password,
            active
         FROM
          dbo.network_users
          WHERE
          {$this->filters} 
          {$this->sortSQL}
         ";

        $users = $this->fetchAllAssoc($sql,$this->filter_params);
        
        if(empty($users)) {
            throw new NotFoundException(array('displayMessage' => 'Could not find any Network users with selected Filters'));
        }

        $return = array();

        // sorry for the nested statements, can't make setters/getters as it's all in one list variable
        foreach($users as $id => $user) {
            $return[$id] = array();
            $keys = array_keys($user);
            foreach($this->fieldMap as $fieldName => $details) {
                if (in_array($details['name'], $keys)) {
                    if (isset($details['type']) && $details['type'] == 'boolean') {
                        $return[$id][$fieldName] = Convert::toBoolean($user[$details['name']]);
                    } else {
                        $return[$id][$fieldName] = $user[$details['name']];
                    }
                }
            }
        }

        $this->list = $return;

        return true;

    }

    public function getAllNetworkUsers()
    {
        return $this->users;
    }
}