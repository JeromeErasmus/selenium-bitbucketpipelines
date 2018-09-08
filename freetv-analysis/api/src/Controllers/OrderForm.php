<?php

/**
 * Description of TvcFormat
 *
 * This controller and model pair save data from the initial order form from the OAS and
 * the final order form confirmation from the job screen
 *
 * The collection retrieves all the submitted order forms from the api
 * @author Jeremy
 */

namespace App\Controllers;

use Elf\Http\Request;
use Elf\Event\RestEvent;
use Elf\Exception\NotFoundException;
use App\Models\OrderForm as Model;

class OrderForm extends RestEvent {
    
    private $collectionName = "orderFormList";
    private $route = "orderForm";
    
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

        $capsule = $this->app->service('eloquent')->getCapsule();
        $model = Model::findOrFail($id);
        return $model->getAsArray();
    }
    
    /**
     * 
     * @param Request $request
     * @return type
     */
    private function getCollection(Request $request)
    {
        $collection = $this->app->collection($this->collectionName);
        $collection->setParams($request->query());
        return $collection->getAll();
    }
    
    public function handlePost(Request $request)
    {
        
        $capsule = $this->app->service('eloquent')->getCapsule();
        $userInput = $request->retrieveJSONInput();
        $model = new Model();
        $model->setFromArray($userInput);

        
        if($model->validate()) {
            $model->save();
            $data = $model->getAsArray();
            $clientId = $request->query('clientId');
            $url = "/".$this->route."/clientId/$clientId/id/" . $data['id'];
            $this->set('locationUrl', $url); 
            $this->set('status_code', 201);
            return;
        }
        
        $this->set('status_code', 400);

        return $model->errors;
 
    }
    
    public function handlePatch(Request $request)
    {
        $id = $request->query('id');

        if(null === $id) {
            throw new NotFoundException(['displayMessage' => 'Unable to update - please specify an order form id']);
        }
        $capsule = $this->app->service('eloquent')->getCapsule();
        $userInput = $request->retrieveJSONInput();
    
        $model = Model::findOrFail($id);
 
        $model->setFromArray($userInput);

        if($model->validate()) {
            $model->save();
            $this->set('status_code', 204);
            return;
        }
        
        $this->set('status_code', 400);

        return $model->errors;
    }
    
    
    public function handleDelete(Request $request)
    {
        $id = $request->query('id');
        if(null === $id) {
            throw new NotFoundException("");
        }
        
        $capsule = $this->app->service('eloquent')->getCapsule();
        $deleted = Model::destroy($id);

        if($deleted) {
            $this->set('status_code', 204);
            return;
        }
        
        throw new NotFoundException("");
    }
    
}
