<?php
/**
 * Created by PhpStorm.
 * User: jeremy.paul
 * Date: 14/01/2016
 * Time: 3:57 PM
 */

namespace App\Controllers;

use App\Models\JobAlert as Model;
use Elf\Http\Request;
use Elf\Event\RestEvent;

class JobAlert extends AppRestController {

    private $collectionName = "jobAlertList";
    private $route = "alert";

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
        return $collection->fetch();
    }

    public function handlePost(Request $request)
    {

        $capsule = $this->app->service('eloquent')->getCapsule();
        $userInput = $request->retrieveJSONInput();
        $userInput['alertSourceUserId'] = $this->app->service('user')->getCurrentUser()->getUserSysid();
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
            throw new NotFoundException("No alert matches the parameters");
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
            throw new NotFoundException("No alert matches the parameters");
        }

        $capsule = $this->app->service('eloquent')->getCapsule();
        $deleted = Model::destroy($id);

        if($deleted) {
            $this->set('status_code', 204);
            return;
        }

        throw new NotFoundException("No alert matches the parameters");
    }
}