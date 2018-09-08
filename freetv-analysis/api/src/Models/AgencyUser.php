<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Models;
use Elf\Application\Application;
use Elf\Exception\ConflictException;
use Elf\Exception\NotFoundException;
use Elf\Exception\ForbiddenException as AccessDeniedException;
use Elf\Utility\Convert;

/**
 * Description of AgencyUser
 *
 * @author Jeremy
 */
class AgencyUser extends \Elf\Db\AbstractAction
{

    public $agency_user_tablename = 'dbo.agency_users';
    public $agencies_tablename = 'dbo.agencies';
    public $network_tablename = 'dbo.networks';

    private $userId;


    public function getUserBySysId($id){
        $params = array(
            ':sysId' => $id,
        );

        $sql = "SELECT
                  user_sysid as userSysid,
                  user_id as userId,
                  user_name as userName,
                  user_first_name as firstName,
                  user_last_name as lastName,
                  user_email as email
                FROM users
                WHERE user_sysid = :sysId
                AND deleted = 0";

        $data = $this->fetchOneAssoc($sql, $params);
        if (empty($data)) {
            throw new NotFoundException("No agency user with sysid $id");
        }

        return $data;
    }
    public function getAgencyUserDetailsBySysId($id){
        $params = array(
            ':sysId' => $id,
        );

        $sql = "SELECT TOP 1000 agu_sysid as userSysid,
                  agu_id as userId,
                  agu_first_name+agu_last_name as userName,
                  agu_first_name as firstName,
                  agu_last_name as lastName,
                  agu_email_address as email
                FROM agency_users
                WHERE agu_sysid = :sysId";

        $data = $this->fetchOneAssoc($sql, $params);
        if (empty($data)) {
            throw new NotFoundException("No agency user with sysid $id");
        }
        if (empty($data['userName'])){
            $data['userName'] = $data['agu_first_name'] +$data['agu_last_name'];
        }
        return $data;
    }
    public function getAgencyUserById($id)
    {
        $params = array(
            ":user_id" => $id,
        );
        $sql = 'SELECT * '
            . 'FROM ' . $this->agency_user_tablename .
            " WHERE agu_id= :user_id";
        $data = $this->fetchOneAssoc($sql, $params);

        if (empty($data)) {
            return false;
        }else {
            if($data['agu_ag_id'] != 0) {
                $parameters[':agencyId'] = $data['agu_ag_id'];
                $sql = ' SELECT * '
                    . ' FROM ' . $this->agencies_tablename .
                    " WHERE ag_id = :agencyId ";
                if ($agency_data = $this->fetchOneAssoc($sql,$parameters)) {
                    $data['agency'] = $agency_data;
                }
            }
            if ($data['agu_is_network_user'] != 0) {
                if ($agency_data['ag_net_id'] != 0 && isset($agency_data['ag_net_id'])) {
                    $sql = 'SELECT *'
                        . 'FROM ' . $this->network_tablename .
                        " WHERE net_id =" . $agency_data['ag_net_id'];
                    $network_data = $this->fetchOneAssoc($sql);
                    $data['network'] = $network_data;
                } else {
                    $data['network'] = false;
                }
            } else {
                $data['network'] = false;
            }
            $primaryGroupId = $data['agu_ag_id'] == null ? '0' : $data['agu_ag_id'];
            $data = $this->mapData($data);

            $params = array(
                ":id" => $id,
                ":primaryGroupId" => $primaryGroupId,
            );
            $sql = 'SELECT	aau_id as agencyPrimaryKey
                            ,aau_ag_id as agencyId
                            ,agencyTable.ag_name as agencyName
                    FROM dbo.agency_agency_user
                    LEFT JOIN dbo.agencies as agencyTable
                    ON agencyTable.ag_id = dbo.agency_agency_user.aau_ag_id
                    WHERE aau_agu_id = :id
                    AND aau_ag_id != :primaryGroupId';

            $data['relatedAgencies'] = $this->fetchAllAssoc($sql, $params);

            return $data;
        }
    }

    public function getAgencyUserBySysId($sysid) {
        $sql = "SELECT agu_id FROM agency_users WHERE agu_sysid = :sysid";

        $data = $this->fetchOneAssoc($sql, [':sysid' => $sysid]);

        if (empty($data)) {
            throw new NotFoundException("No agency user with sysid $sysid");
        }

        return $this->getAgencyUserById($data['agu_id']);

    }

    /**
     * @return array|bool|mixed
     * @throws \Exception
     *
     * Gets an agency user details when only loading agency user id into model
     * (kind of a lazy hacky implementation of the User model)
     */
    public function getAgencyUser()
    {
        $userId = $this->getUserId();
        if (empty($userId)) {
            throw new \Exception("No user Id set");
        }
        return $this->getAgencyUserById($this->getUserId());
    }


    public function createAgencyUser($agencyData)
    {
        $this->app->service('eloquent')->getCapsule();

        if ($this->duplicateUserExists($agencyData['email'])) {
            throw new ConflictException("Agency user with same email address already exists");
        }

        if(isset($agencyData['agencyId'])){
            try {
                $data = Agencies::findOrFail($agencyData['agencyId']);
            } catch (NotFoundException $e) {
                //correct case
                $data = null;
            }

            if (empty($data)) {
                throw new NotFoundException("Cannot find agency Id");
            }
        }
        $sql = "INSERT INTO " . $this->agency_user_tablename . "(
                    agu_ag_id,
                    agu_email_address,
                    agu_first_name,
                    agu_last_name,
                    agu_password,
                    agu_telephone_area_code,
                    agu_telephone,
                    agu_mobile,
                    agu_fax_area_code,
                    agu_fax,
                    agu_is_sync_update,
                    agu_is_active,
                    agu_is_agency_admin)"
            . "VALUES(
                    :agencyId,
                    :emailAddress,
                    :firstName,
                    :lastName,
                    :password,
                    :telephoneareacode,
                    :telephone,
                    :mobile,
                    :faxareacode,
                    :faxnumber,
                    :agu_is_sync_update,
                    :isActive,
                    :agu_is_agency_admin
        )";
        $params = array(
            ':agencyId' => $agencyData['agencyId'],
            ':emailAddress' => $agencyData['email'],
            ':firstName' => $agencyData['firstName'],
            ':lastName' => $agencyData['lastName'],
            ':password' => $this->setPassword($agencyData['password']),
            ':telephoneareacode' => isset($agencyData['areaCodePhone']) ? $agencyData['areaCodePhone'] : null,
            ':telephone' => $agencyData['phone'],
            ':mobile' => $agencyData['mobile'],
            ':faxareacode' => isset($agencyData['areaCodeFax']) ? $agencyData['areaCodeFax'] : null,
            ':faxnumber' => isset($agencyData['fax']) ? $agencyData['fax'] : null,
            ':isActive' => Convert::toBoolean($agencyData['isActive']),
            ':agu_is_sync_update' => true,
            ':agu_is_agency_admin' => isset($agencyData['isAgencyAdmin']) ? Convert::toBoolean($agencyData['isAgencyAdmin']) : null,
        );


        $id = $this->insert($sql, $params);

        return $id;
    }

    public function setPassword($password)
    {
        // @TODO hash according to config the password here.
        $components = $this->app->config->get('components');

        switch ($components['AdsCache']['HashAlg']) {
            case 'plaintext':
                return $password;
            default:
                if (in_array($components['AdsCache']['HashAlg'], hash_algos())) {
                    return hash($components['AdsCache']['HashAlg'], $password);
                } else {
                    throw new \Exception("Config error - unknown hash");
                }
        }
    }

    public function modifyAgencyUser($id, $newData)
    {
        //The user must ALWAYS have a primary agency Id
//        if (isset($newData['agencyId']) && empty($newData['agencyId'])) {
//            throw new NotFoundException("Agency Id not set");
//        }
        if (isset($newData['password']) && !empty($newData['password'])) {
            $newData['password'] = $this->setPassword($newData['password']);
        } else {
            unset($newData['password']);
        }

        if (isset($newData['isActive']) && !empty($newData['isActive'])) {
            $newData['isActive'] = Convert::toBoolean($newData['isActive']);
        }

        if (isset($newData['isAgencyAdmin']) && !empty($newData['isAgencyAdmin'])) {
            $newData['isAgencyAdmin'] = Convert::toBoolean($newData['isAgencyAdmin']);
        }

        //Removes related agencies from the join table if the flag is set and there is an id to disassociate
        if (!empty($newData['disassociateFlag']) && !empty($newData['idToBeDisassociated']) ) {
            if (empty($newData)) {
                return true;
            }
            $this->dissociateAgencyUser($newData['idToBeDisassociated'], $id);
            unset($newData['disassociateFlag']);
            unset($newData['idToBeDisassociated']);
        }

        if (empty($newData)) {
            return true;
        }

        $sql = "SELECT agu_id,agu_password,agu_email_address,agu_first_name,
                agu_last_name,agu_telephone_area_code,agu_telephone,agu_mobile,agu_fax_area_code,agu_fax,
                agu_is_network_manager,agu_is_network_user,agu_ag_id "
            . "FROM " . $this->agency_user_tablename .
            " WHERE agu_id = :id";

        $existingData = $this->fetchOneAssoc($sql, [':id' => $id]);

        if (empty($existingData)) {
            throw new NotFoundException("Agency User Id does not exist");
        }
        $mapping = $this->fieldMap;

        $sql = "UPDATE " . $this->agency_user_tablename . " SET ";
        $setParameters = '';

        $params = [];

        foreach ($newData as $fieldName => $value) {
            if (!array_key_exists($fieldName, $mapping)) {
                throw new \Exception("Unknown field : " . $fieldName . "<br/>");
            }
            if ($newData[$fieldName] === false) {
                $setParameters .= $mapping[$fieldName]['name'] . " = 0 ,";
            } else {
                $setParameters .= " {$mapping[$fieldName]['name']} = :$fieldName,";
                $params[':' . $fieldName] = $value;
            }
        }


        $setParameters = rtrim($setParameters, ',');
        $sql .= $setParameters . " WHERE agu_id = :id";
        $params[':id'] = $id;

        return $this->update($sql, $params);

    }


    private function dissociateAgencyUser($agencyId, $userId)
    {
        $params = array(
            ":agencyPrimaryKey" => $agencyId,
            ":userId" => $userId,
        );

        $selectQuery = "SELECT agu_ag_id, agu_is_agency_admin FROM agency_users WHERE agu_id = :userId";
        $agencyUser = $this->fetchOneAssoc($selectQuery, array(':userId' => $userId));

        if($agencyUser['agu_ag_id'] == $agencyId){
            $sql = 'UPDATE agency_users set agu_ag_id = null, agu_is_agency_admin = 0 WHERE agu_ag_id = :agencyPrimaryKey AND agu_id = :userId';
        }
        else {
            $sql = 'DELETE FROM dbo.agency_agency_user WHERE aau_ag_id = :agencyPrimaryKey AND aau_agu_id = :userId';
        }

        return $this->execute($sql, $params);


    }

    public function getLastId()
    {
        $sql = "SELECT TOP 1 agu_id FROM " .
            $this->agency_user_tablename .
            " ORDER BY agu_id DESC";
        $lastid = $this->fetchOneAssoc($sql, '');
        return $lastid['agu_id'];
    }

    public function deleteAgencyUser($id)
    {
        try {
            $params = array(
                ':agu_id' => $id,
            );
            $sql = "DELETE FROM dbo.agency_agency_user where aau_agu_id = :agu_id";


            $this->execute($sql, $params);

            $sql = "DELETE FROM dbo.agency_users WHERE agu_id = :agu_id;";
            $this->execute($sql, $params);
        } catch (\Exception $ex) {
            return false;
        }
        return true;
    }

    /**
     * Link an agency to an agency user if that relationship does not already exist
     * @param $agenciesToBeLinked
     * @param $sourceId
     * @throws \Exception
     */
    public function linkAgencies($agenciesToBeLinked, $sourceId)
    {
        $agenciesToBeLinked = explode(',', $agenciesToBeLinked);
        $parametrizedValues[':agencyUserId'] = $sourceId;
        $parametrizedValues[':agencyUserId1'] = $sourceId;
        $sql = "
            BEGIN
               IF NOT EXISTS (SELECT * FROM dbo.agency_agency_user
                               WHERE aau_agu_id = :agencyUserId
                               AND aau_ag_id = :agencyId)
               BEGIN
                   INSERT INTO dbo.agency_agency_user (aau_agu_id, aau_ag_id)
                   VALUES (:agencyUserId1, :agencyId1)
               END
            END";

        foreach ($agenciesToBeLinked as $agencyId) {
            $parametrizedValues[':agencyId'] = $agencyId;
            $parametrizedValues[':agencyId1'] = $agencyId;
            $this->execute($sql,$parametrizedValues);
        }

    }

    /**
     * @param $emailAddress
     * @return bool
     * @throws \Exception
     *
     * If duplicate user exists, return true
     */
    public function duplicateUserExists($emailAddress)
    {
        $sql = "SELECT COUNT(*) as count FROM agency_users WHERE agu_email_address = :email";
        $data = $this->fetchOneAssoc($sql, [':email' => $emailAddress]);

        return ($data['count'] == "0") ? false : true;
    }

    //This function takes one data set and maps all the entries to have keys that 
    //match up to the input names, i.e. agu_id will be mapped to agencyId for readableness
    public function mapData($data)
    {
        $fieldmapping = $this->fieldMap;
        $agencyNameMapping = $this->agencyNameMapping;
        $networkNameMapping = $this->networkNameMapping;
        $userData = array();
        foreach ($fieldmapping as $fieldname => $value) {
            if (array_key_exists($fieldmapping[$fieldname]['name'], $data)
                && (isset($fieldmapping[$fieldname]['expose']) &&
                    false !== $fieldmapping[$fieldname]['expose']) || !isset($fieldmapping[$fieldname]['expose'])
            ) {
                $userData[$fieldname] = $data[$fieldmapping[$fieldname]['name']];

                if ($value['type'] === "boolean") {
                    $userData[$fieldname] = Convert::toBoolean($data[$fieldmapping[$fieldname]['name']]);
                }
            }

        }
        //For job collection, to match the API documentation, no mention of agencies or networks are passed through to the response
        if (isset($data['agency'])) {
            if ($data['agency'] === false) {
                unset($data['agency']);
            } else {
                foreach ($agencyNameMapping as $fieldname => $value) {
                    if (array_key_exists($agencyNameMapping[$fieldname]['name'], $data['agency'])) {
                        $userData['agency'][$fieldname] = $data['agency'][$agencyNameMapping[$fieldname]['name']];
                    }
                }
            }
        } else {
            $userData['agency'] = null;
        }

        //For job collection, to match the API documentation, no mention of agencies or networks are passed through to the response
        if (isset($data['network'])) {
            if ($data['network'] === false) {
                $userData['network'] = null;
            } else {
                foreach ($networkNameMapping as $fieldname => $value) {
                    if (array_key_exists($networkNameMapping[$fieldname]['name'], $data['network'])) {
                        $userData['network'][$fieldname] = $data['network'][$networkNameMapping[$fieldname]['name']];
                    }
                }
            }
        } else {
            $userData['network'] = null;
        }
        return $userData;
    }

    public function setLastLogin($userSysId)
    {
        if (empty($userSysId)) {
            throw new \Exception("Cannot set last logged in time");
        }

        $sql = "UPDATE system_users SET sys_last_login = GETDATE() WHERE sysid = :sysid";
        return $this->update($sql, [':sysid' => $userSysId]);
    }

    public function getLastLogin($userSysId)
    {
        if (empty($userSysId)) {
            throw new \Exception("Cannot get last logged in time");
        }

        $sql = "SELECT sys_last_login FROM system_users WHERE sysid = :sysid";
        return $this->fetchOneAssoc($sql, [':sysid' => $userSysId]);
    }

    protected $fieldMap = array(
        'userSysid' => array(
            'name' => 'agu_sysid',
            'type' => 'integer',
            'required' => '',
            'allowEmpty' => ''
        ),
        'agencyId' => array(
            'name' => 'agu_ag_id',
            'type' => 'integer',
            'required' => false,
            //Because we don't know if:
            // we're creating a new agencyUser
            // or
            // if we're creating an agencyAdmin as the same time as an agency
            'allowEmpty' => true
        ),
        'userId' => array(
            'name' => 'agu_id',
            'type' => 'integer',
            'required' => '',
            'allowEmpty' => ''
        ),
        'email' => array(
            'name' => 'agu_email_address',
            'type' => 'string',
            'required' => true,
            'allowEmpty' => ''
        ),
        'firstName' => array(
            'name' => 'agu_first_name',
            'type' => 'string',
            'required' => true,
            'allowEmpty' => ''
        ),
        'lastName' => array(
            'name' => 'agu_last_name',
            'type' => 'string',
            'required' => true,
            'allowEmpty' => ''
        ),
        'password' => array(
            'name' => 'agu_password',
            'type' => 'string',
            'required' => true,
            'allowEmpty' => '',
            'expose' => false,
        ),
        'areaCodePhone' => array(
            'name' => 'agu_telephone_area_code',
            'type' => 'string',
            'required' => false,
            'allowEmpty' => true
        ),
        'phone' => array(
            'name' => 'agu_telephone',
            'type' => 'string',
            'required' => false,
            'allowEmpty' => true
        ),
        'mobile' => array(
            'name' => 'agu_mobile',
            'type' => 'string',
            'required' => false,
            'allowEmpty' => true
        ),
        'areaCodeFax' => array(
            'name' => 'agu_fax_area_code',
            'type' => 'string',
            'required' => false,
            'allowEmpty' => true
        ),
        'fax' => array(
            'name' => 'agu_fax',
            'type' => 'string',
            'required' => false,
            'allowEmpty' => true
        ),
        'isActive' => array(
            'name' => 'agu_is_active',
            'type' => 'boolean',
            'required' => true,
            'allowEmpty' => ''
        ),
        'isAgencyAdmin' => array(
            'name' => 'agu_is_agency_admin',
            'type' => 'boolean',
            'required' => false,
            'allowEmpty' => false
        )
    );

    // Agency name mapping

    protected $agencyNameMapping = array(
        'agencyId' => array(
            'name' => 'ag_id',
            'type' => '',
            'required' => '',
            'allowEmpty' => ''
        ),
        'code' => array(
            'name' => 'ag_code',
            'type' => '',
            'required' => '',
            'allowEmpty' => ''
        ),
        'name' => array(
            'name' => 'ag_name',
            'type' => '',
            'required' => '',
            'allowEmpty' => ''
        ),
        'address1' => array(
            'name' => 'ag_address1',
            'type' => '',
            'required' => '',
            'allowEmpty' => ''
        ),
        'address2' => array(
            'name' => 'ag_address2',
            'type' => '',
            'required' => '',
            'allowEmpty' => ''
        ),
        'city' => array(
            'name' => 'ag_city',
            'type' => '',
            'required' => '',
            'allowEmpty' => ''
        ),
        'state' => array(
            'name' => 'ag_sta_id',
            'type' => '',
            'required' => '',
            'allowEmpty' => ''
        ),
        'postCode' => array(
            'name' => 'ag_postcode',
            'type' => '',
            'required' => '',
            'allowEmpty' => ''
        ),
        'countryCode' => array(
            'name' => 'ag_area_code',
            'type' => '',
            'required' => '',
            'allowEmpty' => ''
        ),
        'areaCodePhone' => array(
            'name' => 'ag_area_code',
            'type' => '',
            'required' => '',
            'allowEmpty' => ''
        ),
        'phone' => array(
            'name' => 'ag_phone',
            'type' => '',
            'required' => '',
            'allowEmpty' => ''
        ),
        'mobile' => array(
            'name' => 'ag_mobile',
            'type' => '',
            'required' => '',
            'allowEmpty' => ''
        ),
        'areaCodeFax' => array(
            'name' => 'ag_fax_area_code',
            'type' => '',
            'required' => '',
            'allowEmpty' => ''
        ),
        'fax' => array(
            'name' => 'ag_fax',
            'type' => '',
            'required' => '',
            'allowEmpty' => ''
        ),
        'corpAffairsNo' => array(
            'name' => 'ag_corp_affairs_no',
            'type' => '',
            'required' => '',
            'allowEmpty' => ''
        ),
        'contactName' => array(
            'name' => 'ag_contact_name',
            'type' => '',
            'required' => '',
            'allowEmpty' => ''
        ),
        'purchaseOrderRequired' => array(
            'name' => 'ag_purchase_order_required',
            'type' => '',
            'required' => '',
            'allowEmpty' => ''
        ),
        'billingCode' => array(
            'name' => 'ag_billing_code',
            'type' => '',
            'required' => '',
            'allowEmpty' => ''
        ),
        'accountType' => array(
            'name' => 'ag_account_type',
            'type' => '',
            'required' => '',
            'allowEmpty' => ''
        ),
        'stopCredit' => array(
            'name' => 'ag_credit_limit',
            'type' => '',
            'required' => '',
            'allowEmpty' => ''
        ),
        'accountGroup' => array(
            'name' => 'ag_account_group',
            'type' => '',
            'required' => '',
            'allowEmpty' => ''
        ),
        'abn' => array(
            'name' => 'ag_abn',
            'type' => '',
            'required' => '',
            'allowEmpty' => ''
        ),
        'approvedLateSubmissionAdvertiserIds' => array(
            'name' => 'ag_allow_late_submission',
            'type' => '',
            'required' => '',
            'allowEmpty' => ''
        ),
    );

    // Network name mapping

    protected $networkNameMapping = array(
        'networkId' => array(
            'name' => 'net_id',
            'type' => '',
            'required' => '',
            'allowEmpty' => ''
        ),
        'name' => array(
            'name' => 'net_name',
            'type' => '',
            'required' => '',
            'allowEmpty' => ''
        ),
    );

    public function load()
    {
    }

    public function save()
    {
    }

    public function getUserSysid()
    {
        $id = $this->getUserId();
        if (empty($id)){
            throw new NotFoundException("No user Id set");
        }

        $data = $this->getAgencyUserById($id);

        if (!empty($data['userSysid'])) {
            return $data['userSysid'];
        }

        throw new \Exception("Cannot find sysid from agency use email");
    }

    public function findOneByEmail($email) {

        $sql = "SELECT agu_id FROM agency_users WHERE agu_email_address = :email";

        $data = $this->fetchOneAssoc($sql, [':email' => $email]);

        if(empty($data)){
            throw new NotFoundException("No user found with email");
        }

        $agencyUser = new AgencyUser($this->app);
        $agencyUser->setUserId($data['agu_id']);
        return $agencyUser;
    }

    /**
     * @return mixed
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param mixed $userId
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;
    }

}