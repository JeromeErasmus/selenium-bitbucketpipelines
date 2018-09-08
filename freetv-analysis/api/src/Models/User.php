<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Models;

use Elf\Db\AbstractAction;
use Elf\Exception as CustomExc;
use Elf\Exception\NotFoundException;
use Elf\Utility\Convert;
use App\Models\UserInterface;

/**
 * Description of User
 *
 * @author michael
 */
class User extends AbstractAction implements UserInterface{

    private $userSysid;
    private $userId;
    private $userName;
    private $userFirstName;
    private $userLastName;
    private $userEmail;
    public $userPassword;
    private $userActive;
    private $userJfiId;
    private $userAllowUpdates;
    private $userRoleId;
    private $userPermissionSet;

    private $passwordEncrypted = false;

    protected $fieldMap = array(
        'userSysid' => array(
            'name' => 'user_sysid',
            'type' => 'numeric',
            'required' => false, // in case this is a new record
            'allowEmpty' => true
        ),
        'userId' => array(
            'name' => 'user_id',
            'type' => 'string',
            'required' => false, // in case this is a new record
            'allowEmpty' => true
        ),
        'userName' => array(
            'name' => 'user_name',
            'type' => 'string',
            'required' => false, // in case this is a new record
            'allowEmpty' => true
        ),
        'userFirstName' => array(
            'name' => 'user_first_name',
            'type' => 'string',
            'required' => true,
            'allowEmpty' => false
        ),
        'userLastName' => array(
            'name' => 'user_last_name',
            'type' => 'string',
            'required' => true,
            'allowEmpty' => false
        ),
        'userEmail' => array(
            'name' => 'user_email',
            'type' => 'string',
            'required' => false,
            'allowEmpty' => false
        ),
        'userPassword' => array(
            'name' => 'user_password',
            'type' => 'string',
            'required' => false,
            'allowEmpty' => true,
            'expose' => false,
        ),
        'userActive' => array(
            'name' => 'user_active',
            'type' => 'boolean',
            'required' => true,
            'allowEmpty' => false
        ),
        'userJfiId' => array(
            'name' => 'user_jfi_id',
            'type' => 'numeric',
            'required' => true,
            'allowEmpty' => false
        ),
        'userAllowUpdates' => array(
            'name' => 'user_allow_updates',
            'type' => 'string',
            'required' => true,
            'allowEmpty' => false
        ),
        'userRoleId' => array(
            'name' => 'user_role_id',
            'type' => 'numeric',
            'required' => true,
            'allowEmpty' => false
        ),
        'userPermissionSet' => array(
            'name' => 'user_permission_set',
            'type' => 'array',
            'required' => true,
            'allowEmpty' => false
        ),
    );



    /**
     * 
     * @param type $app
     * @return \App\Models\User
     */
    public function __construct($app) {
        parent::__construct($app);
        $this->config = $app->config->get('security');
        return $this; // for method chaining
    }

    /**
     * Authenticate user's credentails firstly using ADS, if it fails because LDAP server is not reachable 
     * Authenticate credentials using cad database
     * @param type $username
     * @param type $password
     * @return type
     */
    public function authenticate($username, $password) {

        if ($username != NULL && $password != NULL) {
            try {
                $activeDirectoryService = $this->app->service('Ads');
            } catch (adLDAPException $e) {
                // @TODO error response with error details
            }
            return $activeDirectoryService->authenticate($username, $password);
        }
    }
    /**
     * @param $userId
     * @return User
     * @throws NotFoundException
     * @throws \Exception
     */
    public function findOneByUserId($userId) {
        $params = array(
            ':user_id' => $userId,
        );
        $sql = "SELECT user_sysid FROM dbo.users where dbo.users.user_id = :user_id AND users.deleted <> 1";

        $data = $this->fetchOneAssoc($sql, $params);

        if (!$data) {
            throw new NotFoundException("Cannot Find User with id of $userId");
        }

        $user = new User($this->app);
        $user->setUserSysid($data['user_sysid']);
        $user->load();
        return $user;
    }

    public function findOneByUserSysId($userSysId) {
        $params = array(
            ':user_sysid' => $userSysId,
        );
        $sql = "SELECT user_sysid FROM dbo.users where dbo.users.user_sysid = :user_sysid AND users.deleted <> 1";

        $data = $this->fetchOneAssoc($sql, $params);

        if (!$data) {
            throw new NotFoundException("Cannot Find User with id of $userSysId");
        }

        $user = new User($this->app);
        $user->setUserSysid($data['user_sysid']);
        $user->load();
        return $user;
    }

    /**
     * 
     * @throws Exception
     */
    public function load()
    {
        if (!$this->getUserSysid()) {
            throw new NotFoundException ("sysid not set on user so cannot load it");
        }

        $params = array(
            ':user_sysid' => $this->getUserSysid(),
        );

        $sql = "SELECT * FROM dbo.users where dbo.users.user_sysid = :user_sysid AND users.deleted <> 1";

        $data = $this->fetchOneAssoc($sql, $params);

        if(!empty($data))
        {
            $this->setFromArray($data);
            $this->passwordEncrypted = true;        // since we're loading from the db, the password is already hashed
            return;
        }
        throw new NotFoundException("entity not found");

    }

    public function save() {

        if ($this->passwordEncrypted === false) {
            $this->encryptPassword();
        }

        if (!$this->getUserSysid()) { 
            return $this->create();
        } else {
            return $this->updateUser();
        }
               
    }
    
    public function create()
    {

        $roleMdl = $this->app->model('Role');

        try {
            $permissionSet = json_encode($roleMdl->findOneByRoleId($this->getUserRoleId())->getAsArray()['rolePermissionSet']);
        } catch (\Exception $e) {
            throw $e;
        }

        $params = array(
            ':user_id' => $this->getUserId(),
            ':user_name' => $this->getUserName(),
            ':user_first_name' => $this->getUserFirstName(),
            ':user_last_name' => $this->getUserLastName(),
            ':user_email' => $this->getUserEmail(),
            ':user_password' => $this->getUserPassword(),
            ':user_active' => \App\Utility\Helpers::convertVariableToBool($this->getUserActive()),
            ':user_jfi_id' => $this->getUserJfiId(),
            ':user_allow_updates' => \App\Utility\Helpers::convertVariableToBool($this->getUserAllowUpdates()),
            ':user_role_id' => $this->getUserRoleId(),
            ':user_permission_set' => $permissionSet,
        );


        $sql = "INSERT INTO dbo.users "
                . "(user_id, user_name, user_first_name,user_last_name,user_email,user_password,user_active,user_jfi_id,user_allow_updates,user_role_id,user_permission_set) "
                . "VALUES(:user_id,:user_name, :user_first_name,:user_last_name,:user_email,:user_password,:user_active,:user_jfi_id,:user_allow_updates,:user_role_id,:user_permission_set) ";

       try {
            $id = $this->insert($sql, $params);
            $this->setUserSysid($id);
            $this->setLastLogin();
            return true;
        } catch(\Exception $e) {
            return false;
        }
    }
    
    public function updateUser()
    {
        $params = array(
            ':user_id' => $this->getUserId(),
            ':user_name' => $this->getUserName(),
            ':user_first_name' => $this->getUserFirstName(),
            ':user_last_name' => $this->getUserLastName(),
            ':user_email' => $this->getUserEmail(),
            ':user_password' => $this->getUserPassword(),
            ':user_active' => $this->getUserActive(),
            ':user_jfi_id' => $this->getUserJfiId(),
            ':user_allow_updates' => $this->getUserAllowUpdates(),
            ':user_role_id' => $this->getUserRoleId(),
            ':user_permission_set' => $this->getUserPermissionSetAsString(),
        );



        $sql = "UPDATE dbo.users SET "
                . "user_id = :user_id,"
                . "user_name = :user_name,"
                . "user_first_name = :user_first_name,"
                . "user_last_name = :user_last_name,"
                . "user_email = :user_email,"
                . "user_password = :user_password,"
                . "user_active = :user_active,"
                . "user_jfi_id = :user_jfi_id,"
                . "user_allow_updates = :user_allow_updates,"
                . "user_role_id = :user_role_id, "
                . "user_permission_set = :user_permission_set "
                . "WHERE user_sysid = " . $this->getUserSysid();

       
        
        $this->execute($sql, $params);
        return true;
    }


    public function encryptPassword() {
        if ($this->passwordEncrypted === true) {
            return;
        }

        $password = $this->userPassword;
        $components = $this->app->config->get('components');

        switch ($components['AdsCache']['HashAlg']) {
            case 'plaintext':
                $this->userPassword = $password;
                $this->passwordEncrypted = true;
                break;
            default:
                if (in_array($components['AdsCache']['HashAlg'], hash_algos())) {
                    $this->userPassword = hash($components['AdsCache']['HashAlg'], $password);
                    $this->passwordEncrypted = true;
                    break;
                } else {
                    throw new \Exception("Config error - unknown hash");
                }
        }
    }

    /**
     * set all the properties with an array of data
     * @param $data
     */
    public function setFromArray($data) {
        foreach ($this->fieldMap as $key => $mapping) { //loop through the field map and only pick up data that we want
            $setMethod = "set" . $key;
            if (method_exists($this, $setMethod) && isset($data[$mapping['name']])) { // check if we can actually update this field
                $this->$setMethod($data[$mapping['name']]);
            } else if (method_exists($this, $setMethod) && isset($data[$key])) { // check if we can actually update this field
                $this->$setMethod($data[$key]);
            }
        }
    }
    
    public function getById($id)
    {
        $user = new User($this->app);
        $user->setUserSysid($id);
        $user->load();
        return $user->getAsArray();
    }
    /**
     * Delete a Role by Id
     * @param type $userSysid
     */
    public function deleteById($userSysid) 
    {

        $sql = "UPDATE users SET deleted = 1 WHERE user_sysid = :sysid";
        $rows[] = $this->update($sql, [':sysid' => $userSysid]);

        $sql = "UPDATE system_users SET deleted = 1 WHERE sysid = :sysid";
        $rows[] = $this->update($sql, [':sysid' => $userSysid]);

        if($rows[0] === 0 || $rows[1] === 1)
        {
            throw new \Exception("Could not complete deletion of user with sysid of: " . $userSysid);
        }
    }

    public function setLastLogin()
    {
        $userSysId = $this->getUserSysid();
        if (empty($userSysId)) {
            throw new \Exception("Cannot set last logged in time");
        }

        $sql = "UPDATE system_users SET sys_last_login = GETDATE() WHERE sysid = :sysid";
        return $this->update($sql, [':sysid' => $this->getUserSysid()]);
    }

    public function getLastLogin()
    {
        $userSysId = $this->getUserSysid();
        if (empty($userSysId)) {
            throw new \Exception("Cannot set last logged in time");
        }

        $sql = "SELECT sys_last_login FROM system_users WHERE sysid = :sysid AND deleted <> 1";
        return $this->fetchOneAssoc($sql, [':sysid' => $this->getUserSysid()]);
    }

    /**
     * get the data as an array
     * @return type
     */
    public function getAsArray() {
        $returnArray = array();
        foreach ($this->fieldMap as $key => $mapping) { //loop through the field map and only pick up data that we want
            $getMethod = "get" . ucfirst($key);

            if (method_exists($this, $getMethod) &&
                    (isset($mapping['expose']) &&
                    false !== $mapping['expose']) || !isset($mapping['expose'])
            ) { // check if we can actually update this field
                $returnArray[$key] = $this->$getMethod();
            }
        }

        return $returnArray;
    }

    function getUserSysid() {
        return $this->userSysid;
    }

    function getUserId() {
        return $this->userId;
    }

    function getUserName() {
        return $this->userName;
    }
    function getUserFirstName() {
        return $this->userFirstName;
    }

    function getUserLastName() {
        return $this->userLastName;
    }

    function getUserEmail() {
        return $this->userEmail;
    }

    function getUserPassword() {
        return $this->userPassword;
    }

    function getUserActive() {
        return $this->userActive;
    }

    function getUserJfiId() {
        return $this->userJfiId;
    }

    function getUserAllowUpdates() {
        return $this->userAllowUpdates;
    }

    function getUserRoleId() {
        return $this->userRoleId;
    }

    function getUserPermissionSet() {
        return $this->userPermissionSet;
    }
    
    public function getUserPermissionSetAsString() 
    {
        return json_encode($this->userPermissionSet);
    }

    function getFieldMap() {
        return $this->fieldMap;
    }

    function setUserSysid($userSysid) {
        $this->userSysid = $userSysid;
    }

    function setUserId($userId) {
        $this->userId = $userId;
    }

    function setUserName($userName) {
        $this->userName = $userName;
    }

    function setUserFirstName($userFirstName) {
        $this->userFirstName = $userFirstName;
    }

    function setUserLastName($userLastName) {
        $this->userLastName = $userLastName;
    }

    function setUserEmail($userEmail) {
        $this->userEmail = $userEmail;
    }

    function setUserPassword($userPassword) {
        $this->userPassword = $userPassword;
        $this->passwordEncrypted = false;
    }

    function setUserActive($userActive) {
        $this->userActive = Convert::toBoolean($userActive);
    }

    function setUserJfiId($userJfiId) {
        $this->userJfiId = $userJfiId;
    }

    function setUserAllowUpdates($userAllowUpdates) {
        $this->userAllowUpdates = $userAllowUpdates;
    }

    function setUserRoleId($userRoleId) {
        $this->userRoleId = $userRoleId;
    }

    public function setUserPermissionSet($userPermissionSet) {

        if (is_string($userPermissionSet)) {
            $this->userPermissionSet = json_decode($userPermissionSet, true);
            return;
        }

        $this->userPermissionSet = $userPermissionSet;
    }

    function setFieldMap($fieldMap) {
        $this->fieldMap = $fieldMap;
    }

}
