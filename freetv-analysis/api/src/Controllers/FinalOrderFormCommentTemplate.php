<?php
/**
 * Created by PhpStorm.
 * User: shirleen.sharma
 * Date: 29/10/15
 * Time: 13:58
 */

namespace App\Controllers;


use Elf\Event\RestEvent;
use Elf\Http\Request;
use App\Models\FinalOrderFormCommentTemplate as Model;

class FinalOrderFormCommentTemplate extends RestEvent
{

    private $route = "finalOrderFormCommentTemplate";

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

    public function handlePost(Request $request)
    {
        $capsule = $this->app->service('eloquent')->getCapsule();
        $userInput = $request->retrieveJSONInput();
        $model = new Model();
        $model->setFromArray($userInput);

        if ($model->validate()) {
            $model->save();
            $data = $model->getAsArray();
            $clientId = $request->query('clientId');
            $url = "/" . $this->route . "/clientId/$clientId/id/" . $data['id'];
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

    private function getCollection(Request $request)
    {
        $collection = $this->app->collection('FinalOrderFormCommentTemplateList');
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
        }

    }

}