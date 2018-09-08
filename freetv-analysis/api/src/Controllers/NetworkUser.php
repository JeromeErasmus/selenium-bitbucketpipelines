<?php
/**
 * Created by PhpStorm.
 * User: thimira.gunasekera
 * Date: 16/09/2015
 * Time: 1:47 PM
 */

namespace App\Controllers;

use Elf\Event\RestEvent;
use Elf\Exception\NotFoundException;
use Elf\Http\Request;

class NetworkUser extends RestEvent
{
    public function handleGet(Request $request)
    {
        $id = $request->query('id');
        if(null === $id)
        {
            return $this->getCollection($request);
        }
        $networkUser = $this->app->model('NetworkUser');
        $networkUser = $networkUser->findByUserId($id);
        return $networkUser->getAsArray();
    }

    /**
     * @param Request $request
     * @return array
     *
     * Takes networkId as param e.g /networkUser/networkId/2
     */
    private function getCollection(Request $request)
    {
        $networkUsers = $this->app->collection('NetworkUserlist');

        $networkUsers->setParams($request->query());
        $networkUsers->setOrder($request->query());

        $data = array();
        if ($networkUsers->fetch()) {
            $data = $networkUsers->list;
        }
        return $data;
    }

    public function handlePost(Request $request)
    {
        $inputData = $request->retrieveJSONInput();
        $networkUser = $this->app->model('NetworkUser');
        unset($inputData['networkUserId']);
        unset($inputData['systemId']);

        $networkUser->setFromArray($inputData);

        if(!$networkUser->validate($networkUser->getAsArray(true))){
            $this->set('status_code', 400);
            return $networkUser->getErrors();
        }
        
        if(!$networkUser->save()){
            $this->set('status_code', 400);
            return array("We were unable to complete your query successfully, please try again later or contact a system administrator with code:'NU-IN-F'");
        }

        $clientId = $request->query('clientId');
        $url = "/networkUser/clientId/$clientId/id/{$networkUser->getNetworkUserId()}";
        $this->set('locationUrl', $url);
        $this->set('status_code', 201);
    }
    
    /**
     * update a record
     * @param Request $request
     * @return type
     */
    public function handlePatch(Request $request)
    {
        $id = $request->query('id');
        $inputData = $request->retrieveJSONInput();
        unset($inputData['networkUserId']); // we dont want this

        $user = $this->app->model('networkUser');
        $user->setNetworkUserId($id);
        $user->load();
        $user->setFromArray($inputData);
        if(!$user->validate($user->getAsArray(true))){
            $this->set('status_code', 400);     
            return $user->getErrors();
        }
        $user->save();
        $this->set('status_code', 204);       
    }
    
    /**
     * delete it!
     * @param Request $request
     */
    public function handleDelete(Request $request)
    {
        $id = $request->query('id');
        $user = $this->app->model('networkUser');
        $user->deleteById($id);
        $this->set('status_code', 204);
    }
}