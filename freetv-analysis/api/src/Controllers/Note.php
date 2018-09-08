<?php
namespace App\Controllers;


use Elf\Event\RestEvent;
use Elf\Exception\MalformedException;
use Elf\Exception\NotFoundException;
use Elf\Http\Request;


class Note extends RestEvent
{
    /*
     * 
     * /note/clientId/1/type/{type}/classId/{typeId}
     * EXAMPLE:
     * /note/clientId/1/type/advertiser/classId/11
     *
     * ClassOfNote is what type of note you are making. eg, Advertiser
     * ClassId is the ID which you would like to retrieve all notes for. eg, AdvertiserId
     *
     */
    /**
     * Retrieves all the notes of a given class for a specified id
     *
     * @param Request $request
     * @return mixed
     * @throws NotFoundException
     * @throws \Exception
     */
    public function handleGet(Request $request)
    {
        $id = $request->query('id');
        $type = strtoupper($request->query('type'));
        $classId = $request->query('classId');

        if ($id !== null) {
            $note = $this->app->model('note');
            $note->setNoteId($id);
            $note->load();
            return $note->getFullNote();
        }

        if(empty($type)){
            throw new MalformedException("No Note type given");
        }
        
        if(null === $id) {
            $this->set('status_code', 200);
            $data = $this->getCollection($request, $type, $classId);
            return $data;
        }
    }

    public function getCollection(Request $request, $type = '', $classId)
    {
        $notes = $this->app->collection($type.'Notes');
        $notes->setId($classId);
        $data = $notes->fetch();
        return $data;
    }

    /**
     * handles the post from the form
     *
     * @param Request $request
     * @throws \Elf\Exception\MalformedException
     * @throws \Exception
     */
    public function handlePost(Request $request)
    {
        $type = strtoupper($request->query('type'));
        $classId = strtoupper($request->query('classId'));

        if (empty($type) || empty($classId)){
            throw new MalformedException("note type or classId not set");
        }
        $inputData = $request->retrieveJSONInput();

        $userId = $this->app->service('User')->getCurrentUser()->getUserSysid();
        $inputData['userId'] = $userId;

        //create the note, sending all data to the note model
        $note = $this->app->model('note');
        $noteAssocModel = $this->app->model(strtolower($type).'Note');
        
        //begin a transaction here
        try{
            $this->app->service('Transaction')->beginTransaction();
            $note->setFromArray($inputData, $userId);

            if(!$note->validate($note->getAsArray())){
                $this->set('status_code', 400);
                return $note->getErrors();
            }

            //get the returned new note id
            $newNoteId = $note->save();

            //send the new note id to the appropriate model to associate a note with a class
            $noteAssocModel = $this->app->model(strtolower($type).'Note');

            $noteAssocModel->setAssocIds($newNoteId, $classId);

            if($noteAssocModel->save()){
                //end the transaction here
                $this->app->service('Transaction')->endTransaction();
                $data = $noteAssocModel->getAsArray();
                $clientId = $request->query('clientId');
                $url = "/Note/clientId/$clientId/id/" . $data['noteId'];
                $this->set('locationUrl', $url);
                $this->set('status_code', 201);
                return;
            }
        }
        catch(\Exception $e){
            $this->app->service('Transaction')->rollbackTransaction();
            echo "Failed: " . $e->getMessage();
        }

        $this->set('status_code', 400);

        return $noteAssocModel->errors;
    }

    public function handlePatch(Request $request)
    {
        $id = $request->query('id');

        $inputData = $request->retrieveJSONInput();
        $inputData['userId'] = $this->app->service('User')->getCurrentUser()->getUserSysid();

        $note = $this->app->model('note');
        $note->setNoteId($id);
        $note->load();

        $note->setFromArray($inputData);

        $note->save();
        $this->set('status_code', 204);
    }

    public function handleDelete(Request $request)
    {

        $id = $request->query('id');
        $userId = $this->app->service('User')->getCurrentUser()->getUserSysid();

        if (null === $id) {
            throw new \Exception("No note ID given");
        }
        try {
            $note = $this->app->model('note'); 
            $note->setDeletedById($userId);
            $note->setNoteId($id);
            $note->load();
            $note->deleteRecord();
        } catch (MalformedException $e) {
            $this->set('status_code', 400);
            return (array('code' => 400, 'message' => $e->getMessage()));
        }
        $this->set('status_code', 204);

    }
}
