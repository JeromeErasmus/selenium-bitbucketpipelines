<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Controllers;


use Elf\Http\Request;
use Elf\Event\RestEvent;
use App\Models\MyMetaModel;


class LastSelectedFilter extends RestEvent {
    
    public function handleGet(Request $request)
    {
        
        // @TODO authorize requestNo response received
        $id = $request->query('id');

        //get the last selected filter
        $sysId = $this->app->service('user')->getCurrentUser()->getUserSysid();
        $lastSelectedFilterMdl = $this->app->model('LastSelectedFilter');
        $filterData = $lastSelectedFilterMdl->getFilterBySysId($sysId);
        return $filterData;
    }
    
    public function handlePost(Request $request)
    {

        // @TODO authorize request
        //$sysId = $this->app->service('user')->getCurrentUser()->getUserSysid();
        
        $data = $request->retrieveJSONInput();

        $lastSelectedFilterMdl = $this->app->model('LastSelectedFilter');
        $userId = $this->app->service('user')->getCurrentUser()->getUserId();

        $lastSelectedFilterMdl->setLastSelectedFilter($userId, $data);
        $clientId = $request->query('clientId');
        $url = "/cad/clientId/$clientId";
        $this->set('locationUrl', $url);                      
           
        $this->set('status_code', 201);
        return json_encode("ok");
        
    }
    
    public function handlePatch(Request $request)
    {
        // @TODO authorize request
        $id = $request->query('id');
        
        $data = $request->retrieveJSONInput();
        $filterMdl = $this->app->model('Filter');
        
        try {
            $id = $filterMdl->modifyFilter($id, $data);
        } catch (\Exception $ex) {
            $this->set('status_code', 404);
            //log some error here
            return;
        }
                
        $this->set('status_code', 204);
               
    }
    
    public function handleDelete(Request $request)
    {
        // @TODO authorize request
        $id = $request->query('id');

        if ($id === null) {
            throw new \Exception("No ID given.");
        }
        
        $filterMdl = $this->app->model('Filter');
        try {
            if($filterMdl->deleteFilter($id)) {
                $this->set('status_code', 204);
            } else {
                $this->set('status_code', 404);
            }
        } catch (\Exception $ex) {
            echo "Error: ".$ex->getMessage();
            die();
        }

        return;
    }
}
