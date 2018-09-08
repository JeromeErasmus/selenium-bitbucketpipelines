<?php

/**
 * Description of Contact
 *
 * @author Jeremy
 */

namespace App\Controllers;

use Elf\Http\Request;
use Elf\Event\RestEvent;
use Elf\Exception\NotFoundException;
use App\Models\Contact as Model;

class Contact extends RestEvent {
    
    private $entity = "contact";
    private $collectionName = "contactList";
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
        return Model::with('notifications')->findOrFail($id)->toRestful();
    }
    
    
    public function handlePost(Request $request)
    {
        $now = new \DateTime();
        $capsule = $this->app->service('eloquent')->getCapsule();
        $userInput = $request->retrieveJSONInput();
        $userInput['createdAt'] = $now->format('Y-m-d H:i:s');
        $userInput['createdBy'] = $this->app->service('user')->getCurrentUser()->getUserSysid();

        if(isset($userInput['contactableType'])) {
            $userInput['contactableType'] = "App\\Models\\" .$userInput['contactableType'];
        } else {
            $userInput['contactableType'] = "App\\Models\\Agencies";        //default to not break existing stuff
        }

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
            throw new NotFoundException("");
        }
        $capsule = $this->app->service('eloquent')->getCapsule();
        $userInput = $request->retrieveJSONInput();
        if(isset($userInput['contactableType'])) {
            $userInput['contactableType'] = "App\\Models\\" .$userInput['contactableType'];
        } else {
            $userInput['contactableType'] = "App\\Models\\Agencies";        //default to not break existing stuff
        }
        $userInput['updatedAt'] = $now->format('Y-m-d H:i:s');
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
