<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Controllers;

use Elf\Exception\ConflictException;
use Elf\Exception\NotFoundException;
use Elf\Http\Request;
use Elf\Event\RestEvent;


class ManualAdjustmentType extends AppRestController
{

    /**
     * 
     * @param Request $request
     * @return type
     */
    public function handleGet(Request $request)
    {
        
        
        $id = $request->query('id');
        if(null !== $id) {
            $manualAdjTypeModel = $this->app->model('ManualAdjustmentType');
            $manualAdjTypeModel->setId($id);
            $manualAdjTypeModel->load();
            if ($manualAdjType = $manualAdjTypeModel->getAsArray()) {
                $this->set('status_code', 200);
                return $manualAdjType;
            }
        }else{
           
            return $this->getCollection($request);
        }
        
        throw new NotFoundException(['displayMessage' => 'No result set found - please specify a key number id or a job id']);

    }
    
    /**
     * get the collection
     * @param type $request
     * @return type
     */
    public function getCollection($request)
    {
        $collection = $this->app->collection('manualadjustmenttypelist');
        
        $collection->setParams($request->query());
  
        return $collection->getAll();
    }
    
    /**
     * handle POST 
     * @param Request $request
     * @return type
     */
    public function handlePost(Request $request)
    {
        $inputData = $request->retrieveJSONInput();
        $keyNumberModel = $this->app->model('KeyNumber');
        $keyNumberModel->setFromArray($inputData);

        if(!$keyNumberModel->validate($keyNumberModel->getAsArray(true))){
            $this->set('status_code', 400);
            return $keyNumberModel->getErrors();
        }
        
        $keyNumberModel->save();
        $keyNumberId = $keyNumberModel->getTvcId();
        $clientId = $request->query('clientId');

        if($keyNumberId && $clientId) {
            $url = "/cad/clientId/$clientId/id/{$keyNumberModel->getTvcId()}";
            $this->set('locationUrl', $url);
            $this->set('status_code', 201);
        } else {
            $this->set('status_code', 400);
        }

    }
    
    /**
     * update a record
     * @param Request $request
     * @return type
     */
    public function handlePatch(Request $request)
    {
        $id = $request->query('id');
        if(null !== $id) {
            $inputData = $request->retrieveJSONInput();
            $keyNumberModel = $this->app->model('KeyNumber');
            $keyNumberModel->setTvcId($id);
            $keyNumberModel->load();

            $keyNumberModel->setFromArray($inputData);
            if(!$keyNumberModel->validate($keyNumberModel->getAsArray(true))){
                $this->set('status_code', 400);
                return $keyNumberModel->getErrors();
            }
            if($keyNumberModel->save()) {
                $this->set('status_code',204);
                return;
            }
        } else {
            throw new NotFoundException(['displayMessage' => 'Unable to update - please specify a TVC id']);
        }
    }
    
    /**
     * delete it!
     * @param Request $request
     */
    public function handleDelete(Request $request)
    {
        if($id = $request->query('id')) {
            $keyNumberModel = $this->app->model('KeyNumber');
            $keyNumberModel->setTvcId($id);
            if($keyNumberModel->deleteTvc()) {
                $this->set('status_code', 204);
                return;
            } else {
                throw new NotFoundException(['displayMessage' => 'Unable to update - please specify a TVC id']);
            }
        } else {
            throw new NotFoundException(['displayMessage' => 'Unable to update - please specify a TVC id']);
        }
    }
    
}
