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


class Filter extends RestEvent {
    
    public function handleGet(Request $request)
    {
        // @TODO authorize request
        $id = $request->query('id');

        /* retrieve by id */
        if (isset($id)) {
            $filterMdl = $this->app->model('Filter');

            $filterData = $filterMdl->getFilterById($id);

            if ($filterData === false) {
                $this->set('status_code', 404);
                return;
            } else {
                return $filterData;
            }
        } else {    // retrieve filter collection

            $filterType = $request->query('type');
            $userId = $request->getBasicAuthFromHeaders()[0];       //temporary
            $filterCol = $this->app->collection('Filterlist');

            $filters = array();
            if ( isset($filterType) ) {
                switch (strtolower($filterType)) {
                    case 'all':
                        $filters = $filterCol->getUserFilters("$userId|prebuilt_filters");
                        break;
                    case 'prebuilt':
                        $filters = $filterCol->getUserFilters('prebuilt_filters');
                        break;
                    case 'custom':
                        $filters = $filterCol->getUserFilters($userId);
                        break;
                    default:        //bad request 400
                        $this->set('status_code', 400);
                        return;
                }
            } else {        //return only custom filters if no param set
                $filters = $filterCol->getUserFilters($userId);
            }
            return $filters;
        }
    }
    
    public function handlePost(Request $request)
    {
        // @TODO authorize request
        $username = $request->getBasicAuthFromHeaders()[0];

        $data = $request->retrieveJSONInput();
        $filterMdl = $this->app->model('Filter');
        try {
            $id = $filterMdl->createFilter($username, $data);
        } catch (\Exception $ex) {
            print "Error: {$ex->getMessage()}";
        }
      
        $clientId = $request->query('clientId');
        $url = "/cad/clientId/$clientId/id/$id";
        $this->set('locationUrl', $url);            
        $this->set('status_code', 201);
        
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
