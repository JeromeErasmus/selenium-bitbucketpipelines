<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Controllers;

use Elf\Http\Request;
use Elf\Event\RestEvent;

/**
 * Description of Role
 *
 * @author michael
 */
class User extends RestEvent 
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

        $user = $this->app->model('user');
        $user->setUserSysid($id);
        $user->load();
        return $user->getAsArray();

    }
    
    /**
     * get the collection
     * @param type $request
     * @return type
     */
    public function getCollection($request)
    {
        $roles = $this->app->collection('userlist');
        return $roles->getAllUsers();
    }
    
    /**
     * handle POST 
     * @param Request $request
     * @return type
     */
    public function handlePost(Request $request)
    {
        $inputData = $request->retrieveJSONInput();
        $user = $this->app->model('user');
        
        $user->setFromArray($inputData);

        if(!$user->validate($user->getAsArray())){
            $this->set('status_code', 400);
            return $user->getErrors();
        }
        
        if(!$user->save()){
            $this->set('status_code', 400);
            return array("We were unable to complete your query successfully, please try again later or contact a system administrator with code:'US-IN-F'");
        }

        $clientId = $request->query('clientId');
        $url = "/user/clientId/$clientId/id/{$user->getUserSysid()}";
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
        unset($inputData['userSysid']); // we dont want this
        if(array_key_exists('userPassword',$inputData) && empty($inputData['userPassword'])) {
            unset($inputData['userPassword']);
        }
        $user = $this->app->model('user');
        $user->setUserSysid($id);
        $user->load();
        $user->setFromArray($inputData);
        if(!$user->validate($user->getAsArray())){
            $this->set('status_code', 400);
            return $user->getErrors();
        }
        $user->save();
        $this->set('status_code', 204);       
    }
    
    /**
     * delete it!
     * @param Request $request
     */
    public function handleDelete(Request $request)
    {
        $id = $request->query('id');
        $user = $this->app->model('user');
        $user->deleteById($id);
        $this->set('status_code', 204);
    }
    
}
