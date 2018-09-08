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

class TvcCost extends RestEvent {
    
    public function handleGet(Request $request) {
        //Get's a single cost entry, or all cost entries
        if ($id = $request->query('id')){
            
            $tvcMdl = $this->app->model('TvcCost');
            
            if ($value = $tvcMdl->getTvcCostById($id)) 
            {
                $this->set('status_code', 200);
                return $value ;
            } else {
                throw new \Exception("Couldn't fetch TVC Costs.");
            }
        } else {
            
            $tvcMdl = $this->app->model('TvcCost');
            
            if ($value = $tvcMdl->getTvcCosts()) 
            {
                $this->set('status_code', 200);
                return $value ;
            } else {
                throw new \Exception("Couldn't fetch TVC Costs.");
            }
        }
            
    }
    
    public function handlePost(Request $request) {
        
        $tvcMdl = $this->app->model('TvcCost');

        $tvcPostData = $request->retrieveJSONInput();
        
        if($tvcMdl->validate($tvcPostData)){           
            if ($id = $tvcMdl->createTvcCost($tvcPostData)) {
                $clientId = $request->query('clientId');
                $url = "/cad/clientId/$clientId/id/$id";
                $this->set('locationUrl', $url);            
                $this->set('status_code', 201);
            } else {
                throw new \Exception("Unable to create new entry");
            }
        } else {
            throw new \Exception("Invalid input");
        }
      
    }
    
    public function handlePatch(Request $request) {
        
        $id = $request->query('id');
        
        $data = $request->retrieveJSONInput();
  
        $tvcMdl = $this->app->model('TvcCost');
        
        if(!empty($id)) {
            
            if($tvcMdl->modifyTvcCost($id, $data)) {
                $this->set('status_code', 204);
            } else {
                throw new \Exception("Failed to update record");
            }
        } else {
            throw new \Exception("No ID.");
        }
    }
    
    public function handleDelete(Request $request) {
        
        $id = $request->query('id');
        
        $tvcMdl = $this->app->model('TvcCost');
        
        if ($tvcMdl->deleteCostEntry($id)){
            $this->set('status_code', 204);
        } else {
            throw new \Exception("Failed to delete");
        }        
    }
}
