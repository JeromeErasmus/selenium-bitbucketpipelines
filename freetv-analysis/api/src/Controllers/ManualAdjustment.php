<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Controllers;

use Elf\Http\Request;
use Elf\Exception\NotFoundException;
use App\Models\ManualAdjustment as Model;

class ManualAdjustment extends AppRestController
{
    public $collectionName = "manualAdjustmentList";
    private $route = "manualAdjustment";
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
        if(null !== $request->query('jobId')){
            $userInput['jobId'] = $request->query('jobId');
        }
        $model = new Model();
        //calculate the GST for the input amount
        $userInput['gst'] = bcdiv($userInput['maAmount'],'11',2); //divide by 11 and ceil to two decimals
        $userInput['exgst'] = bcsub($userInput['maAmount'] , $userInput['gst'], 2);
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
            throw new NotFoundException(['displayMessage' => 'Unable to update - please specify a manual adjustment id']);
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
            throw new NotFoundException(['displayMessage' => 'Unable to update - please specify a manual adjustment id']);
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
