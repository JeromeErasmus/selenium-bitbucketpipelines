<?php
/**
 * Created by PhpStorm.
 * User: thimira.gunasekera
 * Date: 8/09/2015
 * Time: 11:39 AM
 */

namespace App\Controllers;

use Elf\Event\RestEvent;
use Elf\Exception\ConflictException;
use Elf\Exception\MalformedException;
use Elf\Http\Request;

class Advertiser extends RestEvent{

    public function handleGet(Request $request)
    {
        $id = $request->query('advertiserId');
        $name = $request->query('advertiserName');
        $advertiserMdl = $this->app->model('advertiser');

        if($request->query('findDuplicates')){
            $value = $advertiserMdl->findDuplicates($id,$name);
            $this->set('status_code', 200);
            return $value;
        }
        if(null === $id)
        {
            return $this->getCollection($request);
        }

        else{
            $advertiser = $advertiserMdl->findByAdvertiserId($id);
        }
        return $advertiser->getAsArray();
        return $advertiserMdl->load($request->query('id'));
    }

    public function handlePatch(Request $request)
    {
        $id = $request->query('advertiserId');
        if (null === $id) {
            throw new \Exception("No advertiser ID given");
        }
        try {
            $advertiser = $this->app->model('advertiser')->findByAdvertiserId($id);
            $advertiser->setFields($request->retrieveJSONInput());
            $advertiser->save();
        } catch (MalformedException $e) {
            $this->set('status_code', 400);
            return (array('code' => 400, 'message' => $e->getMessage()));
        }
        $this->set('status_code', 204);
    }
    
    
    public function handleDelete(Request $request)
    {

        $id = $request->query('advertiserId');

        if (null === $id) {
            throw new \Exception("No advertiser ID given");
        }
        try {
            $advertiser = $this->app->model('advertiser');
            $advertiser->setAdvertiserId($id);
            $advertiser->load();
            $advertiser->deleteRecord();
        } catch (MalformedException $e) {
            $this->set('status_code', 400);
            return (array('code' => 400, 'message' => $e->getMessage()));
        }
        $this->set('status_code', 204);
    }
    

    public function handlePost(Request $request)
    {
        try {
            $advertiser = $this->app->model('advertiser');
            $advertiser->setFields($request->retrieveJSONInput());           
            $id = $advertiser->save();
            $clientId = $request->query('clientId');
            $url = "/advertiser/clientId/$clientId/advertiserId/$id";
            $this->set('locationUrl', $url);
            $this->set('status_code', 201);

        } catch (MalformedException $e) {

            $this->set('status_code', 400);
            return (array('code' => 400, 'message' => $e->getMessage()));

        } catch (ConflictException $e ) {

            $this->set('status_code', 409);
            return (array('code' => 409, 'message' => $e->getMessage()));
        } catch (Exception $e) {
             $this->set('status_code', 402);
            return (array('code' => 402, 'message' => $e->getMessage()));
        }


    }

    public function getCollection(Request $request)
    {
        $advList = $this->app->collection('Advertiserlist');
        $filters = $request->query();

        $advList->setParams($filters);
        $data = array();
        if ( $advList->fetch() )
        {
            $data = $advList->list;
        }
        return $data;
    }
}