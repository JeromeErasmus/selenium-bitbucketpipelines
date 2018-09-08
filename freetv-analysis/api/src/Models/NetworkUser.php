<?php
/**
 * Created by PhpStorm.
 * User: thimira.gunasekera
 * Date: 16/09/2015
 * Time: 10:57 AM
 */

namespace App\Models;


use App\Utility\Helpers;
use Elf\Db\AbstractAction;
use Elf\Exception\ConflictException;
use Elf\Exception\NotFoundException;
use Elf\Utility\Convert;

class NetworkUser extends  AbstractAction
{
    public $networkUserId;
    private $systemId;
    private $agencyId;
    private $networkId;
    private $firstName;
    private $lastName;
    private $email;
    private $password;
    private $active;

    private $passwordEncrypted = false;


    protected $fieldMap = array(
        'networkUserId' => array(
            'name' => 'id',
            'type' => 'integer',
            'required' => false,
            'allowEmpty' => true,
        ),
        'systemId' => array(
            'name' => 'user_sysid',
            'type' => 'integer',
            'required' => false,
            'allowEmpty' => true,
        ),
        'agencyId' => array(      //this is a holdover from the old system and is not required FCR-888
            'name' => 'agency_id',
            'type' => 'integer',
            'required' => false,
            'allowEmpty' => true,
        ),
        'networkId' => array(
            'name' => 'net_id',
            'type' => 'integer',
            'required' => true,
            'allowEmpty' => false,
        ),
        'firstName' => array(
            'name' => 'first_name',
            'type' => 'string',
            'required' => true,
            'allowEmpty' => false,
        ),
        'lastName' => array(
            'name' => 'last_name',
            'type' => 'string',
            'required' => true,
            'allowEmpty' => false,
        ),
        'email' => array(
            'name' => 'email_address',
            'type' => 'string',
            'required' => true,
            'allowEmpty' => false,
        ),
        'password' => array(
            'name' => 'password',
            'type' => 'string',
            'expose' => false,
            'required' => true,
            'allowEmpty' => false,
        ),
        'active' => array(
            'name' => 'active',
            'type' => 'boolean',
            'required' => true,
            'allowEmpty' => false,
        ),

    );

    public function load()
    {
        if (empty($this->networkUserId) ) {
            throw new NotFoundException("No network user ID present.");
        }

        $sql = "SELECT
          user_sysid,
          agency_id,
          net_id,
          first_name,
          last_name,
          password,
          email_address,
          active
           FROM
          dbo.network_users
          WHERE id = :id";

        $user = $this->fetchOneAssoc($sql, array(':id' => $this->networkUserId));

        
        if (empty($user)) {
           throw new NotFoundException("Could not find network user");
        }

        foreach ($this->fieldMap as $fieldName => $details)
        {
            if(in_array($details['name'], array_keys($user))) {
                $setMethod = 'set' . $fieldName;
                $this->$setMethod($user[$details['name']]);
            }
        }
        $this->passwordEncrypted = true;        //db passwords are (should) always be encrpyted
    }

    public function save()
    {
        if ($this->passwordEncrypted === false) {
            $this->encryptPassword();
        }

        if (!empty($this->networkUserId)) {
            return $this->updateRecord();
        } else {
            return $this->createRecord();
        }
    }

    public function findByUserId($id)
    {
        $sql = "SELECT id FROM dbo.network_users WHERE id = :id";
        $result = $this->fetchOneAssoc($sql, array(':id' => $id));

        if (!$result) {
            throw new NotFoundException("Cannot find user with id $id");
        }

        $networkUser = new NetworkUser($this->app);
        $networkUser->networkUserId = $result['id'];
        $networkUser->load();

        return $networkUser;
    }

    public function findBySysId($sysId)
    {
        $sql = "SELECT id FROM dbo.network_users WHERE user_sysid = :sysid";
        $result = $this->fetchOneAssoc($sql, array(':sysid' => $sysId));

        if (!$result) {
            throw new NotFoundException("Cannot find user with id $sysId");
        }

        $networkUser = new NetworkUser($this->app);
        $networkUser->networkUserId = $result['id'];
        $networkUser->load();

        return $networkUser;

    }

    /**
     * @param bool $ignoreExpose ignores the expose attribute in the field map
     * @return array
     */
    public function getAsArray($ignoreExpose = false) {
        $returnArray = array();
        foreach ($this->fieldMap as $key => $mapping) { //loop through the field map and only pick up data that we want
            $getMethod = "get" . ucfirst($key);

            if (method_exists($this, $getMethod) && (isset($mapping['expose']) && false !== $mapping['expose']) || !isset($mapping['expose'])
            ) { // check if we can actually update this field
                $returnArray[$key] = $this->$getMethod();
            } else if (method_exists($this, $getMethod) && $ignoreExpose === true) {
                $returnArray[$key] = $this->$getMethod();
            }
        }

        return $returnArray;
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

    private function updateRecord()
    {
         $params = array(
            ':agency_id' => $this->getAgencyId(),
            ':net_id' => $this->getNetworkId(),
            ':first_name' => $this->getFirstName(), 
            ':last_name' => $this->getLastName(), 
            ':email_address' => $this->getEmail(), 
            ':password' => $this->getPassword(), 
            ':active' => $this->getActive(),
        );



        $sql = "UPDATE dbo.network_users SET "
                . "agency_id = :agency_id,"
                . "net_id = :net_id,"
                . "first_name = :first_name,"
                . "last_name = :last_name,"
                . "email_address = :email_address,"
                . "password = :password,"
                . "active = :active "
                . "WHERE id = " . $this->getNetworkUserId();

       
        
        $this->execute($sql, $params);
        return true;
    }

    private function createRecord()
    {
        $sql = "SELECT id FROM dbo.network_users WHERE email_address = :email";


        $records = $this->fetchOneAssoc($sql, array(':email' => $this->email));

        if ( $records !== false ) {     //a key already exists, fail gracefully
            throw new ConflictException("Network User with email code already exists.");
        }


        $sql = "INSERT INTO dbo.network_users(net_id, first_name, last_name, email_address, password, active)
                VALUES(:net_id, :first_name, :last_name, :email_address, :password, :active)";
        try {
            $id = $this->insert($sql, array(
                ':net_id' => $this->networkId,
                ':first_name' => $this->firstName, 
                ':last_name' => $this->lastName, 
                ':email_address' => $this->email, 
                ':password' => $this->password, 
                ':active' => $this->active,
            ));
        } catch(\Exception $e) {
            die($e->getMessage());
        }

        $this->networkUserId = $id;
        
        return $id;
    }

    /**
     * Delete a Role by Id
     * @param type $userSysid
     */
    public function deleteById($id) 
    {      
        $sql = "DELETE FROM dbo.network_users WHERE id = :id";        
        $rows = $this->delete($sql, array(":id" => $id));
        if($rows === 0)
        {
            throw new \Exception("could not delete entity with id: " . $id);
        }
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
        if (empty($userSysId) ) {
            throw new \Exception("Cannot set last logged in time");
        }

        $sql = "SELECT sys_last_login FROM system_users WHERE sysid = :sysid";
        return $this->fetchOneAssoc($sql, [':sysid' => $userSysId]);
    }


    /**
     * @throws \Exception
     *
     * Encrypts the password set in $this->password as long as it's not already encrpyted (defined by $this->passwordEncrypted)
     *
     */
    public function encryptPassword() {

        if ($this->passwordEncrypted === true) {
            return;
        }
        $password = $this->password;
        $security = $this->app->config->get('security');

        switch ($security['HashAlg']) {
            case 'plaintext':
                $this->password = $password;
                $this->passwordEncrypted = true;
                break;
            default:
                if (in_array($security['HashAlg'], hash_algos())) {
                    $this->password = hash($security['HashAlg'], $password);
                    $this->passwordEncrypted = true;
                    break;
                } else {
                    throw new \Exception("Config error - unknown hash");
                }
        }
    }

    /**
     * @param $password
     *
     * setting a password always sets the flag to be password not encrypted
     */
    public function setPassword($password) {
        $this->password = $password;
        $this->passwordEncrypted = false;
    }

    function getPassword() {
        return $this->password;
    }
  
    function getNetworkUserId() {
        return $this->networkUserId;
    }

    function getSystemId() {
        return $this->systemId;
    }
    
    function getUserSysid() {
        return $this->getSystemId();
    }

    function getAgencyId() {
        return $this->agencyId;
    }

    function getFirstName() {
        return $this->firstName;
    }

    function getLastName() {
        return $this->lastName;
    }

    function getEmail() {
        return $this->email;
    }

    function getActive() {
        return $this->active;
    }

    function setNetworkUserId($networkUserId) {
        
        if(is_numeric($networkUserId)) {
            $networkUserId = intval($networkUserId);
        }
        
        $this->networkUserId = $networkUserId;
    }

    function setSystemId($systemId) {
        
        if(is_numeric($systemId)) {
            $systemId = intval($systemId);
        }
        
        $this->systemId = $systemId;
    }

    function setAgencyId($agencyId) {
        
        if(is_numeric($agencyId)) {
            $agencyId = intval($agencyId);
        }
        
        $this->agencyId = $agencyId;
    }

    function setFirstName($firstName) {
        $this->firstName = $firstName;
    }

    function setLastName($lastName) {
        $this->lastName = $lastName;
    }

    function setEmail($email) {
        $this->email = $email;
    }

    function setActive($active) {
        
        $value = Convert::toBoolean($active);
        $this->active = $value;
        
    }

    function getNetworkId() {
        
        return $this->networkId;
    }

    function setNetworkId($networkId) {
        
        if(is_numeric($networkId)) {
            $networkId = intval($networkId);
        }
        
        $this->networkId = $networkId;
    }
    
    
    public function findOneByEmail($email) {

        $sql = "SELECT id FROM network_users WHERE email_address = :email";

        $data = $this->fetchOneAssoc($sql, [':email' => $email]);

        if(empty($data)){
            throw new NotFoundException("No user found with email");
        }

        $networkUser = new NetworkUser($this->app);
        return $networkUser->findByUserId($data['id']);
    }
    
}