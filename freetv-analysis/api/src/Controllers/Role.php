<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Controllers;

use Elf\Exception\ConflictException;
use Elf\Http\Request;
use Elf\Event\RestEvent;

/**
 * Description of Role
 *
 * @author michael
 */
class Role extends RestEvent 
{

    /**
     * 
     * @param Request $request
     * @return type
     */
    public function handleGet(Request $request)
    {

        $id = $request->query('id');
        if(null === $id)
        {
            return $this->getCollection($request);
        }

        $role = $this->app->model('role');
        $role->setRoleId($id);
        $role->load();
        return $role->getAsArray();

    }
    
    /**
     * get the collection
     * @param type $request
     * @return type
     */
    public function getCollection($request)
    {
        $roles = $this->app->collection('rolelist');
        return $roles->getAllRoles();
    }
    
    /**
     * handle POST 
     * @param Request $request
     * @return type
     */
    public function handlePost(Request $request)
    {
        $inputData = $request->retrieveJSONInput();
        $role = $this->app->model('role');
        $role->setFromArray($inputData);

        if(!$role->validate($role->getAsArray())){
            return $role->getErrors();
        }
        
        $role->save();
        $clientId = $request->query('clientId');
        $url = "/cad/clientId/$clientId/id/{$role->getRoleId()}";
        $this->set('locationUrl', $url);            
        $this->set('status_code', 201);

    }
    
    /**
     * update a record
     * @param Request $request
     * @return type
     */
    public function handlePatch(Request $request)
    {
        $id = $request->query('id');
        $inputData = $request->retrieveJSONInput();
        unset($inputData['roleId']); // we dont want this
        $role = $this->app->model('role');
        $role->setRoleId($id);
        $role->load();
        $role->setFromArray($inputData);
        if(!$role->validate($role->getAsArray())){
            return $role->getErrors();
        }
        $role->save();
        $this->set('status_code', 204);       
    }
    
    /**
     * delete it!
     * @param Request $request
     */
    public function handleDelete(Request $request)
    {
        $id = $request->query('id');
        $role = $this->app->model('role');
        $role->deleteById($id);
        $this->set('status_code', 204);
    }
    
}
