<?php
/**
 * Created by PhpStorm.
 * User: adam
 */

namespace App\Controllers;


use Elf\Event\RestEvent;
use Elf\Exception\NotFoundException;
use Elf\Http\Request;
use App\Models\RequirementsTemplate as Model;

class RequirementsTemplate extends RestEvent
{
    private $route = "requirementsTemplates";

    public function handleGet(Request $request)
    {
        $id = $request->query('id');
        $this->app->service('eloquent')->getCapsule();

        $catId = $request->query('categoryId');
        $searchTerm = $request->query('searchTerm');
        $data = [];
        if ($catId) {
            $states = Model::where('rma_category_id', '=', $catId)->get();
            foreach ($states as $state) {
                $data[] = $state->getAsArray();
            }
            return $data;
        } else if (null === $id) {

            if ($searchTerm) {
                $searchTerm = urldecode($searchTerm);
                $templates = Model::where('rma_agency_description','LIKE', "%$searchTerm%")
                    ->where('rma_active' , '=' , '1')
                    ->orWhere('rma_short_description', 'LIKE', "%$searchTerm%")
                    ->where('rma_active' , '=' , '1')
                    ->get();
            } else {
                $templates = Model::all();
            }

            foreach ($templates as $template) {
                $data[] = $template->getAsArray();
            }

            if(empty($data)){
                throw new NotFoundException("No results");
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
            throw new NotFoundException("No id present");
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
            throw new NotFoundException("No id present");
        }
        $capsule = $this->app->service('eloquent')->getCapsule();
        $deleted = Model::destroy($id);

        if($deleted) {
            $this->set('status_code', 204);
        }

    }

}