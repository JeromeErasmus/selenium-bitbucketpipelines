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

class NetworkList extends AbstractCollection
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
        'networkName' => array(
            'name' => 'net_name'
        ),
        'networkCode' => array(
            'name' => 'net_code'
        ),
        'email' => array(
            'name' => 'email_address'
        ),
        'active' => array(
            'name' => 'active'
        )

    );


    public function setParams($params = array()){
        if(!empty($params['networkId'])) {
            $this->filters .= "AND net_id = :net_id";
            $this->filter_params[':net_id'] = $params['networkId'];
        }
        $this->filters .= " AND deleted_at IS NULL";
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
            net_id,
            net_code,
            net_name
         FROM
          dbo.networks
          WHERE
          {$this->filters} 
          {$this->orderSql}
         ";

        $networks = $this->fetchAllAssoc($sql,$this->filter_params);
        
        if(empty($networks)) {
            throw new NotFoundException(array('displayMessage' => 'Could not find any Network with selected Filters'));
        }

        $return = array();

        foreach($networks as $network) {
            $temp = array();
            foreach($this->fieldMap as $fieldName => $details) {
                if (in_array($details['name'], array_keys($network))) {
                    $temp[$fieldName] = $network[$details['name']];
                }
            }
            array_push($return, $temp);
        }

        $this->list = $return;

        return true;

    }

    public function getAllNetworks()
    {
        return $this->list;
    }
}