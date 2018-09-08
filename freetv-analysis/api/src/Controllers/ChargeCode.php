<?php
namespace App\Controllers;


use Elf\Event\RestEvent;
use Elf\Exception\MalformedException;
use Elf\Exception\NotFoundException;
use Elf\Http\Request;


class ChargeCode extends RestEvent
{

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

        $editable = $request->query('editable');

        if(!is_null($request->query('submittedDate'))){
            $submittedDate = urldecode($request->query('submittedDate'));
        }else{
            $submittedDate = date("Y-m-d H:i:s");
        }
        
        $restrictions = $request->query('restrict');
        $charityStatus = $request->query('charity');
        $excludeActiveCheck = $request->query('excludeActiveCheck');
        $chargeCodes = $this->app->model('chargeCode');

        $chargeCodes->setCharityStatus($charityStatus);
        $chargeCodes->setExcludeActiveCheck($excludeActiveCheck);
        $chargeCodes->setChargeCodeId($id);
        $chargeCodes->setSubmittedDate($submittedDate);

        if($restrictions == 'OAS') {
            $chargeCodes->restrictForOASUsers();
        }
        if ($id !== null) {
            //retrieves a single charge code identified by ID
            return $chargeCodes->load();
        }

        if(isset($editable)){
            return $chargeCodes->getAllEditableChargeCodes();
        }
        else{
            return $chargeCodes->getEffectiveChargeCodes();
        }
    }

    public function handlePost(Request $request)
    {

        try {
            $chargeCode = $this->app->model('chargeCode');
            $chargeCode->setSingleInputArray($request->retrieveJSONInput());
            $id = $chargeCode->save();
            $clientId = $request->query('clientId');
            $url = "/chargeCode/clientId/$clientId/chargeCodeId/$id";
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

    public function handlePatch(Request $request)
    {
        $id = $request->query('id');
        $inputData = $request->retrieveJSONInput();

        $chargeCode = $this->app->model('chargeCode');
        $chargeCode->setChargeCodeId($id);
        $chargeCode->load();

        $chargeCode->setSingleInputArray($inputData);

        $chargeCode->save();
        $this->set('status_code', 204);
    }

    public function handleDelete(Request $request)
    {
        $id = $request->query('id');
        if (null === $id) {
            throw new \Exception("No Charge Code ID given");
        }

        try {
            $chargeCode = $this->app->model('chargeCode');
            $chargeCode->setChargeCodeId($id);
            $chargeCode->load();
            $chargeCode->deleteRecord();
        } catch (MalformedException $e) {
            $this->set('status_code', 400);
            return (array('code' => 400, 'message' => $e->getMessage()));
        }
        $this->set('status_code', 204);
    }
}