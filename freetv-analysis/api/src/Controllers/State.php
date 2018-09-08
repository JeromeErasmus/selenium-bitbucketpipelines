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
use App\Models\State as Model;

class State extends RestEvent {
    
    private $route = "state";

    
    /**
     * 
     * @param Request $request
     * @return type
     */
    public function handleGet(Request $request) 
    {

        $id = $request->query('id');
        $capsule = $this->app->service('eloquent')->getCapsule();
        if(null === $id)
        {
            $data = [];
            $states = Model::all();
            foreach($states as $state) {
                $data[] = $state->getAsArray();
            }
            return $data;
        }

        
        $model = Model::findOrFail($id);
        return $model->getAsArray();
        
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
            $url = "/" . $this->route .  "/clientId/$clientId/id/" . $data['id'];
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
