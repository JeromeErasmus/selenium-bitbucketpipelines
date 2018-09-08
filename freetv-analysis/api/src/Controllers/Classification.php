<?php

/**
 * Retrieves classification codes from the database
 *
 * @author Jeremy
 */

namespace App\Controllers;

use Elf\Http\Request;
use Elf\Event\RestEvent;
use Elf\Exception\NotFoundException;
use App\Models\Classification as Model;

class Classification extends RestEvent {
    
    private $entity = "classification";
    private $collectionName = "classificationList";
    private $data = array();

    /**
     * 
     * @param Request $request
     * @return type
     */
     public function handleGet(Request $request) 
    {
        $id = $request->query('id');
        $capsule = $this->app->service('eloquent')->getCapsule();

        if(null === $id) {
            return $this->getCollection($request);
        }
        return Model::findOrFail($id)->toRestful();
    }
    
    
    public function handlePost(Request $request)
    {
        $now = new \DateTime();
        $capsule = $this->app->service('eloquent')->getCapsule();
        $userInput = $request->retrieveJSONInput();

        $model = new Model();
        $model->setFromArray($userInput);

        if($model->validate()) {
            $model->save();
            $data = $model->getAsArray();
            $clientId = $request->query('clientId');
            $url = "/" . $this->entity .  "/clientId/$clientId/id/" . $data['id'];
            $this->set('locationUrl', $url); 
            $this->set('status_code', 201);
            return;
        }
        
        $this->set('status_code', 400);

        return $model->errors;
 
    }
    
    public function handlePatch(Request $request)
    {
        $now = new \DateTime();
        $id = $request->query('id');
        if(null === $id) {
            throw new NotFoundException("Classification not found");
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

    private function getCollection(Request $request)
    {
        $collection = $this->app->collection($this->collectionName);
        $collection->setParams($request->query());
        return $collection->getAll();
    }
    
    public function handleDelete(Request $request)
    {
        $id = $request->query('id');
        if(null === $id) {
            throw new NotFoundException("Classification not found");
        }
        
        $capsule = $this->app->service('eloquent')->getCapsule();
        $deleted = Model::destroy($id);

        if($deleted) {
            $this->set('status_code', 204);
            return;
        }
        
        throw new \Exception("Request failed");
    }
}
