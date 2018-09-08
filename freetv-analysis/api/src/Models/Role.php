<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Models;

use Elf\Db\AbstractAction;

/**
 * Description of Role
 *
 * @author michael
 */
class Role extends AbstractAction 
{

    public $roleId = null;
    public $roleName;
    public $roleSlug;
    public $rolePermissionSet;
    
    protected $fieldMap = array(
        'roleId' => array(
            'name' => 'role_id',
            'type' => 'numeric',
            'required' => false, // in case this is a new record
            'allowEmpty' => true
        ),
        'roleName' => array(
            'name' => 'role_name',
            'type' => 'string',
            'required' => true,
            'allowEmpty' => false
        ),
        'roleSlug' => array(
            'name' => 'role_slug',
            'type' => 'string',
            'required' => true,
            'allowEmpty' => false
        ),
        'rolePermissionSet' => array(
            'name' => 'role_permission_set',
            'type' => 'array',
            'required' => false,
            'allowEmpty' => true
        ),
    );
    
    /**
     * 
     * @param type $app
     * @return \App\Models\Role
     */
    public function __construct ($app)
    {
        parent::__construct($app);
        return $this; // for method chaining
    }

    /**
     * 
     * @throws Exception
     */
    public function load()
    {
        
        $this->clear(); // reset all the fields before trying to load
        
        if(!$this->roleId)
        {
            throw new \Exception("roleId not set on model so cannot load it");
        }
        
        $params = array(
            ':role_id' => $this->roleId,
        );
        
        $sql = "SELECT role_id, role_name, role_slug, role_permission_set FROM dbo.roles where dbo.roles.role_id = :role_id";
        
        $data = $this->fetchOneAssoc($sql, $params);
        
        if(false === $data) {
           throw new \Exception("No Role found with id " . $this->roleId);
        }

        $this->setFromArray($data);

    }
    
    
    /**
     * 
     * @param type $userId
     * @return \App\Models\User
     */
    public function findOneByRoleId($roleId) {
        $params = array(
            ':role_id' => $roleId,
        );
        $sql = "SELECT role_id FROM dbo.roles where dbo.roles.role_id = :role_id";

        $data = $this->fetchOneAssoc($sql, $params);

        if (!$data) {
            throw new \Exception("Cannot Find Role with id of $roleId");
        }

        $role = new Role($this->app);
        $role->setRoleId($data['role_id']);
        $role->load();
        return $role;
    }
     
    /*
     * persist the model to the db create and update
     */
    public function save() 
    {
        if(null === $this->roleId) { // new record so create it
            $this->create();  
        } else { // existing record so update it
            $this->updateRecord();
        }
    }
    
    /**
     * Delete a Role by Id
     * @param type $roleId
     */
    public function deleteById($roleId) 
    {
        $sql = "DELETE FROM dbo.roles WHERE role_id = :role_id";        
        $rows = $this->delete($sql, array(":role_id" => $roleId));
        if($rows === 0)
        {
            throw new \Exception("could not delete entity with id: " . $roleId);
        }
    }
    
    /**
     * set all the properties with an array of data
     * @param type $data
     */
    public function setFromArray($data)
    {
        foreach($this->fieldMap as $key => $mapping) { //loop through the field map and only pick up data that we want
            $setMethod = "set".$key;
            if(method_exists($this, $setMethod) && isset($data[$mapping['name']])) { // check if we can actually update this field
                $this->$setMethod($data[$mapping['name']]);
            } else if(method_exists($this, $setMethod) && isset($data[$key])) { // check if we can actually update this field
                $this->$setMethod($data[$key]);
            }
        }
    }
    
    /**
     * get the data as an array
     * @return type
     */
    public function getAsArray()
    {
        return array(
            'roleId' => $this->getRoleId(),
            'roleName' => $this->getRoleName(),
            'roleSlug' => $this->getRoleSlug(),
            'rolePermissionSet' => $this->getRolePermissionSet(),
        );
    }
    
    /**
     * create a record from the objects fields
     */
    private function create()
    {
        $sql =  "INSERT INTO dbo.roles(role_name,role_slug,role_permission_set) VALUES(?, ?, ?)";
        $id = $this->insert($sql, array($this->getRoleName(), $this->getRoleSlug(), $this->getRolePermissionSetAsString()));
        $this->setRoleId($id);
    }
    
    /**
     * update a record from the object's fields
     */
    private function updateRecord()
    {

        $sql =  "UPDATE dbo.roles SET role_name = :role_name, role_slug = :role_slug, role_permission_set = :role_permission_set WHERE role_id = :role_id";
        $this->update($sql, array(
            ':role_id' => $this->getRoleId(),
            ':role_name' => $this->getRoleName(),
            ':role_slug' => $this->getRoleSlug(),
            ':role_permission_set' => $this->getRolePermissionSetAsString(),
        ));

    }
    
    /**
     * clear the object fields
     */
    private function clear()
    {
        $this->setFromArray(array(
            'role_id' => null,
            'role_name' => null,
            'role_slug' => null,
            'role_permission_set' => null,
        ));
    }

    public function getRoleId() 
    {
        return $this->roleId;
    }

    public function getRoleName() 
    {
        return $this->roleName;
    }

    public function getRoleSlug() 
    {
        return $this->roleSlug;
    }

    public function getRolePermissionSet() 
    {
        return $this->rolePermissionSet;
    }
    
    public function getRolePermissionSetAsString() 
    {
        return json_encode($this->rolePermissionSet);
    }

    public function setRoleId($roleId) 
    {
        $this->roleId = $roleId;
    }

    public function setRoleName($roleName) 
    {
        $this->roleName = $roleName;
    }

    public function setRoleSlug($roleSlug) 
    {
        $this->roleSlug = $roleSlug;
    }

    public function setRolePermissionSet($rolePermissionSet) 
    {
        
        if(is_string($rolePermissionSet)) {
            $this->rolePermissionSet = json_decode($rolePermissionSet, true);
            return;
        }
        
        $this->rolePermissionSet = $rolePermissionSet;
    }

}
