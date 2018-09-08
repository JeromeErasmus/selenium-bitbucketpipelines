<?php
/**
 * Created by PhpStorm.
 * User: thimira.gunasekera
 * Date: 9/09/2015
 * Time: 3:24 PM
 */

namespace App\Controllers;


use Elf\Exception\ConflictException;
use Elf\Http\Request;
use Elf\Event\RestEvent;

class AdvertiserCategory extends RestEvent
{

    public function handleGet(Request $request)
    {
        $id = $request->query('id');
        if(null === $id)
        {
            return $this->getCollection($request);
        }

        $advCategory = $this->app->model('AdvertiserCategory');
        $advCategory = $advCategory->findCategoryById($id);
        return $advCategory->getAsArray();
    }

    public function getCollection(Request $request)
    {
        $advCategoryList = $this->app->collection('AdvertiserCategoryList');

        $data = array();
        if ( $advCategoryList->fetch() )
        {
            $data = $advCategoryList->list;
        }
        return $data;
    }

    public function handlePost(Request $request)
    {
        $inputData = $request->retrieveJSONInput();
        $advCategory = $this->app->model('AdvertiserCategory');
        unset($inputData['categoryId']);

        $advCategory->setFromArray($inputData);

        $advCategory->save();

        $clientId = $request->query('clientId');
        $url = "/AdvertiserCategory/clientId/$clientId/id/{$advCategory->getCategoryId()}";
        $this->set('locationUrl', $url);
        $this->set('status_code', 201);
    }

    public function handlePatch(Request $request)
    {
        $id = $request->query('id');
        $inputData = $request->retrieveJSONInput();
        unset($inputData['id']); // we dont want this
        $advCategory = $this->app->model('AdvertiserCategory');

        $advCategory->setFromArray(array('categoryId' => $id));
        $advCategory->load();

        $advCategory->setFromArray($inputData);

        $advCategory->save();
        $this->set('status_code', 204);
    }

    public function handleDelete(Request $request)
    {
        $id = $request->query('id');
        $advCategory = $this->app->model('AdvertiserCategory');
        $advCategory->deleteById($id);
        $this->set('status_code', 204);
    }


}