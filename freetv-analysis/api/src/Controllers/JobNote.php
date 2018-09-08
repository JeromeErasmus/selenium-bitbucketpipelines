<?php
/**
 * Created by PhpStorm.
 * User: thimira.gunasekera
 * Date: 28/09/2015
 * Time: 4:19 PM
 */

namespace App\Controllers;


use Elf\Event\RestEvent;
use Elf\Exception\NotFoundException;
use Elf\Http\Request;


class JobNote extends AppRestController
{
    public function handleGet(Request $request)
    {
        $id = $request->query('id');
        if(null === $id) {
            return $this->getCollection($request);
        }

        $jobNotes = $this->app->model('jobNote');
        $jobNotes->setJobNoteId($id);
        $jobNotes->load();
        return $jobNotes->getAsArray();
    }


    public function getCollection(Request $request)
    {
        $jobNotes = $this->app->collection('JobNoteList');

        $filters = $request->query();
        $jobNotes->setParams($filters);
        
        $jobNotes->fetch();

        $data = $jobNotes->list;
        
        if($data === false) {
            throw new NotFoundException("");
        }
        
        return $jobNotes->getFullJobNotes();
    }

    public function handlePost(Request $request)
    {
        $jobNotes = $this->app->Model('JobNote');
        $data = $request->retrieveJSONInput();

        $jobNotes->setFromArray($data);
        if(!$jobNotes->validate($jobNotes->getAsArray())){
            $this->set('status_code', 400);
            return $jobNotes->getErrors();
        }
        $jobNotes->save();


        $clientId = $request->query('clientId');
        $url = "/jobNotes/clientId/$clientId/id/{$jobNotes->getJobNoteId()}";

        $this->set('locationUrl', $url);
        $this->set('status_code', 201);
    }
}