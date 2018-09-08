<?php

/**
 * Description of TvcFormat
 *
 * @author adam
 */

namespace App\Controllers;

use Elf\Http\Request;
use Elf\Event\RestEvent;
use Elf\Exception\NotFoundException;
use App\Models\TvcFormat as Model;

class TvcFormat extends RestEvent {
    
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
        $model = Model::find($id);
                
        if($model instanceof Model) {
            return $model->getAsArray();
        }
        
        throw new NotFoundException(["displayMessage" => "Could not find entity with id " . $id]);
        
    }
    
    /**
     * 
     * @param Request $request
     * @return type
     */
    private function getCollection(Request $request)
    {
        $collection = $this->app->collection('TvcFormatList');
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
            $url = "/tvcFormat/clientId/$clientId/id/" . $data['id'];
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
            throw new NotFoundException("");
        }
        $capsule = $this->app->service('eloquent')->getCapsule();
        $userInput = $request->retrieveJSONInput();
        $model = Model::find($id);
        
        if(!($model instanceof Model)) {
             throw new NotFoundException("");
        }
        
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
        }
       
    }
    
}
