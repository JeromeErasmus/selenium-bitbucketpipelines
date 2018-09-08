<?php

namespace App\Controllers;

use Elf\Http\Request;
use Elf\Event\RestEvent;
use Elf\Exception\NotFoundException;
use App\Models\Notification as Model;

class Notification extends RestEvent {

    private $collectionName = "notificationList";
    private $route = "notification";

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
        $now = new \DateTime();
        $capsule = $this->app->service('eloquent')->getCapsule();
        $userInput = $request->retrieveJSONInput();
        $userInput['createdAt'] = $now->format('Y-m-d H:i:s');
        $userInput['createdBy'] = $this->app->service('user')->getCurrentUser()->getUserSysid();
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
        $now = new \DateTime();
        $id = $request->query('id');

        if(null === $id) {
            throw new NotFoundException("");
        }
        $capsule = $this->app->service('eloquent')->getCapsule();
        $userInput = $request->retrieveJSONInput();
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
