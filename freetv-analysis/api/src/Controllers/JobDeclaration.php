<?php

/**
 * Description of TvcFormat
 *
 * @author adam
 */

namespace App\Controllers;

use Elf\Exception\ConflictException;
use Elf\Http\Request;
use Elf\Event\RestEvent;
use Elf\Exception\NotFoundException;
use App\Models\JobDeclaration as Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;


class JobDeclaration extends RestEvent {
    
    private $route = "jobDeclaration";
    
    /**
     * 
     * @param Request $request
     * @return type
     */
    public function handleGet(Request $request) 
    {        
        $id = $request->query('jobId');
        $capsule = $this->app->service('eloquent')->getCapsule();

        $model = Model::findOrFail($id);
        return $model->getAsArray();
    }
    
    public function handlePost(Request $request)
    {
        $capsule = $this->app->service('eloquent')->getCapsule();
        $jobDeclarationInput = $request->retrieveJSONInput();

        $requestedId = $request->query('jobId');

        try {
            $model = Model::where('jde_job_id', $requestedId)->firstOrFail();
            throw new ConflictException("The declaration for this job already exists");
        } catch (ModelNotFoundException  $e) {
            // do nothing, this is the correct case
        }

        try {
            $model = Model::withTrashed()->where('jde_job_id', $requestedId)->firstOrFail();
            $model = Model::withTrashed()->where('jde_job_id', $requestedId)->restore();
            $model = Model::where('jde_job_id', $requestedId)->firstOrFail();
        } catch (ModelNotFoundException  $e) {
            // do nothing, this is the correct case
            $model = new Model();
        }

        $model->setFromArray($jobDeclarationInput);

        if($model->validate()) {
            $model->save();
            $data = $model->getAsArray();
            
            $clientId = $request->query('clientId');
            $url = "/".$this->route."/clientId/$clientId/jobId/" . $jobDeclarationInput['jobId'];
            $this->set('locationUrl', $url); 
            $this->set('status_code', 201);
            return;
        }
        
        $this->set('status_code', 400);

        return $model->errors;
    }

    public function handleDelete(Request $request)
    {
        /** @var Model $model */
        $id = $request->query('jobId');
        $capsule = $this->app->service('eloquent')->getCapsule();
        $model = Model::findOrFail((int)$id);
        $model->delete();

        return true;
    }

    public function handlePatch(Request $request){
        $id = $request->query('jobId');
        $capsule = $this->app->service('eloquent')->getCapsule();

        try {
            $model = Model::findOrFail($id);
        } catch (ModelNotFoundException  $e) {
            $model = Model::withTrashed()->findOrFail($id);
        }

        $jobDeclarationInput = $request->retrieveJSONInput();
        $model->setFromArray($jobDeclarationInput);
        if($model->validate()) {
            $model->save();
            $data = $model->getAsArray();

            $clientId = $request->query('clientId');
            $this->set('status_code', 204);
            return;
        }

        $this->set('status_code', 400);

        return $model->errors;
    }
}
