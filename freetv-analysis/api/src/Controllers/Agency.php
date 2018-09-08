<?php

namespace App\Controllers;

use Elf\Http\Request;
use Elf\Event\RestEvent;
use App\Models\MyMetaModel;
use App\Utility\Helpers;

class Agency extends RestEvent
{
    // handleGet services requests to retrieve agencies by their Id's or names or if neither parameter is provided, gets the collection

    public function handleGet(Request $request)
    {
        $name = '';
        $id = '';
        if (($id = $request->query('agencyId')) && !($request->query('agencyByName')) && !($request->query('findDuplicates')) && !($request->query('export'))) {
            $agencyModel = $this->app->model('Agency');
            $agencyModel->setAgencyId($id);

            if($request->query('withTokens')){
                $westpacTokenModel = $this->app->model('WestpacToken');
                $westpacTokenModel->setAgencyId($id);
                $westpacTokenModel->load();
            }

            $agencyModel->load();
            if ($agencyData = $agencyModel->getAsArray()) {
                $this->set('status_code', 200);
                if($request->query('withTokens') ){
                    $agencyData['westpacTokens'] = $westpacTokenModel->getList();
                }
                return $agencyData;

            }
        } elseif (!($request->query('agencyByName')) && !($request->query('agencyId')) && !($request->query('findDuplicates')) ) {
            $agencyListModel = $this->app->collection('AgencyList');
            $agencyListModel->setParameters($request);
            if ($value = $agencyListModel->getAgencyCollection()) {
                $this->set('status_code', 200);
                return $value;
            }

        } elseif ( ($request->query('findDuplicates')) && ( ($id = $request->query('id')) || ($name = $request->query('agencyName')) ) ) {
            $agencyListModel = $this->app->collection('AgencyList');
            $agencyListModel->setParameters($request);
            if ($value = $agencyListModel->findDuplicateAgencies($id, $name)) {
                $this->set('status_code', 200);
                return $value;
            }
        }
        else {
            $this->set('status_code', 400);
        }
    }

    public function handlePost(Request $request)
    {
        $inputData = $request->retrieveJSONInput();
        $agencyModel = $this->app->model('Agency');

        if(isset($inputData['westpacTokens'])){
            $westpacTokens = $inputData['westpacTokens'];
            unset($inputData['westpacTokens']);
            $westpacTokenMdl = $this->app->model('WestpacToken');
        }

        $agencyModel->setFromArray($inputData);

        if( null!== $agencyModel->getBillingCode() && null !== $agencyModel->getAgencyCode() ){
            $agencyModel->setBillingCode($agencyModel->getAgencyCode());
        }

        if(!$agencyModel->validate($agencyModel->getAsArray(true))) {
            $this->set('status_code', 400);
            return $agencyModel->getErrors();
        }

        $agencyModel->checkForDuplicates();
        $agencyModel->save();

        if(isset($westpacTokens)){
            foreach($westpacTokens as $westpacToken){
                //process each token sent back as a save
                $westpacToken['agencyId'] = $agencyModel->getAgencyId();
                $westpacTokenMdl->setFromArray($westpacToken);
                $lastSavedToken = $westpacTokenMdl->save();
                if(isset($westpacToken['primary']) && $westpacToken['primary'] == true){
                    $agencyModel->setPrimaryWestpacToken($lastSavedToken);
                }
            }
            $agencyModel->save();
        }

        $clientId = $request->query('clientId');

        $url = "/agency/clientId/$clientId/agencyId/" . $agencyModel->getAgencyId();
        $this->set('locationUrl', $url);
        $this->set('status_code', 201);
    }

    // Updates an Agency database entry, using either the agency name, or it's ID as the parameters
    public function handlePatch(Request $request)
    {
        $inputData = $request->retrieveJSONInput();

        // if any data on KFI is changed, prepare to update the flag to 0
        if ($this->app->service("agency")->isKFIDataUpdated($request->query('agencyId'), $inputData)) {

            $inputData['isSyncUpdate'] = 0;

        }

        $agencyModel = $this->app->model('Agency');
        
        if(isset($inputData['westpacTokens'])){
            $westpacTokens = $inputData['westpacTokens'];
            unset($inputData['westpacTokens']);
            $westpacTokenMdl = $this->app->model('WestpacToken');
        }

        $agencyModel->setFromArray($inputData);

        $id = $request->query('agencyId');

        if(isset($westpacTokens)){
            foreach($westpacTokens as $westpacToken){
                //process each token sent back as a save
                $westpacToken['agencyId'] = $id;
                $westpacTokenMdl->setFromArray($westpacToken);
                $lastSavedToken = $westpacTokenMdl->save();
                if(isset($westpacToken['primary']) && $westpacToken['primary'] == true){
                    $agencyModel->setPrimaryWestpacToken($lastSavedToken);
                }
            }
        }

        if ($id !== null && !empty($inputData['linkAgencyUser'])) {
            $this->app->service('agency')->linkAgencyUser($inputData['linkAgencyUser'], $id);
            $this->set('status_code', 204);
            return;
        }

        if(!empty($id) &&
            array_key_exists("mergeWith", $inputData) &&
            !empty($inputData["mergeWith"])) {

            $this->app->service("agency")->merge($inputData["mergeWith"], $id);
            $this->set('status_code', 204);
            return null;
        }

        if (($id = $request->query('agencyId'))) {
            $agencyModel->setAgencyId($id);
            $agencyModel->checkForDuplicates(true);

            if(array_key_exists('stopCreditId', $inputData) &&
                is_null(Helpers::convertToNull($inputData['stopCreditId']))) {
                $this->app->service('agency')->removeStopCredit($id);
            }

            if ($agencyModel->save()) {
                $this->set('status_code', 204);
            }
        } elseif (($agencyName = $request->query('agencyName')) && !($request->query('agencyId'))) {
            $agencyModel->setAgencyId($agencyName);
            if ($agencyModel->save()) {
                $this->set('status_code', 204);
            }
        } elseif (!($request->query('agencyName')) && !($request->query('agencyId'))) {
            $this->set('status_code', 400);
        }
    }

    // Deletes an agency database entry either by a supplied ID or a supplied name of the agency
    public function handleDelete(Request $request)
    {
        if (($id = $request->query('agencyId')) && !($request->query('agencyName'))) {
            $agencyModel = $this->app->model('Agency');
            $agencyModel->setAgencyId($id);
            if ($agencyModel->deleteAgency()) {
                $this->set('status_code', 204);
                return;
            }
        } elseif (($agencyName = $request->query('agencyName')) && !($request->query('agencyId'))) {
            $agencyModel = $this->app->model('Agency');
            $agencyModel->setAgencyName($agencyName);
            if ($agencyModel->deleteAgency()) {
                $this->set('status_code', 204);
                return;
            }
        }
        $this->set('status_code', 400);
    }

}