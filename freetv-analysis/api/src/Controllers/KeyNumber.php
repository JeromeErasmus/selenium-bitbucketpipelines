<?php

namespace App\Controllers;

use Elf\Http\Request;
use App\Utility\Helpers;
use App\Models\TvcRequirement;
use Elf\Exception\ConflictException;
use Elf\Exception\NotFoundException;

/**
 * Description of Role
 *
 * @author michael
 */
class KeyNumber extends AppRestController
{

    /**
     * @param Request $request
     * @return type
     * @throws NotFoundException
     */
    public function handleGet(Request $request)
    {
        $id = $request->query('id');
        if(null !== $id) {
            $keyNumberModel = $this->app->model('KeyNumber');
            $keyNumberModel->setTvcId($id);
            $keyNumberModel->load();
            if ($keyNumberData = $keyNumberModel->getAsArray()) {
                $this->set('status_code', 200);
                return $keyNumberData;
            }
        }

        $jobId = $request->query('jobId');
        $action = $request->query('action');
        if(null !== $jobId) {
            return $this->getCollection($request,$action);
        }

        throw new NotFoundException(['displayMessage' => 'No result set found - please specify a key number id or a job id']);

    }

    /**
     * get the collection
     * @param type $request
     * @return type
     */
    public function getCollection($request,$action)
    {

        $params = $request->query();
        $collection = $this->app->collection('keynumberlist');

        $collection->setParams($params);
        if(isset($params['deleted'])){
            $collection->setDeleted($params['deleted']);
        }
        //  In case we add more actions
        switch($action) {
            case 'validateKeyNumbers' :
                return $collection->validateKeyNumbers();
                break;
            default :

                $keyNumbers = $collection->getAll();

                if(!empty($params['includeRequirementCategories'])) {
                    $keyNumbers = $this->addRequirementCategoriesToKeyNumbers($keyNumbers);
                }

                return $keyNumbers;

        }

    }

    /**
     *
     * @param type $keyNumbers
     * @return type
     */
    public function addRequirementCategoriesToKeyNumbers($keyNumbers)
    {

        foreach($keyNumbers as $index => $keyNumber){

            $capsule = $this->app->service('eloquent')->getCapsule();

            $tvcRequirements = TvcRequirement::with('listRequirements')->where('rtv_tvc_id', $keyNumber['tvcId'])->get();

            $requirementCategories = [];

            foreach($tvcRequirements as $tvcRequirement) {

                foreach($tvcRequirement->listRequirements as $requirement) {

                    if(null === $requirement->req_category) {
                        continue;
                    }

                    $requirementCategories[] = $requirement->req_category;

                }

                $requirementCategories = array_unique($requirementCategories);

                $keyNumbers[$index]['requirementCategories'] = $requirementCategories;
            }

        }

        return $keyNumbers;

    }


    private function unsetPOSTInputsForCADNumbers($inputData)
    {
        if(isset($inputData['cadNumber'])){
            unset($inputData['cadNumber']);
        }
        if(isset($inputData['expiryDate'])){
            unset($inputData['expiryDate']);
        }
        if(isset($inputData['assignedBy'])){
            unset($inputData['assignedBy']);
        }
        if(isset($inputData['assignedDate'])){
            unset($inputData['assignedDate']);
        }

        return $inputData;
    }

    /**
     * @param Request $request
     * @return mixed
     * @throws ConflictException
     * @throws \Elf\Exception\MalformedException
     * @throws \Exception
     */
    public function handlePost(Request $request)
    {
        $inputData = $request->retrieveJSONInput();

        $inputData = $this->unsetPOSTInputsForCADNumbers($inputData);

        // If an agency user is attempting to create a key number to be revised after the fact, they must submit
        // all the fields checked below, or an exception will be thrown

        if(!empty($inputData['originalTvcId'])) {
            $jobRevision = $this->app->Model('JobRevision');
            $originalFields = $jobRevision->validateKeyNumberForRevisions($inputData);
            $inputData = array_merge($inputData,$originalFields);
        }

        $keyNumberModel = $this->app->model('keyNumber');

        $keyNumberModel->setFromArray($inputData);

        if(!$keyNumberModel->validate($keyNumberModel->getAsArray(true))){
            $this->set('status_code', 400);
            return $keyNumberModel->getErrors();
        }

        $keyNumberModel->save();
        $keyNumberId = $keyNumberModel->getTvcId();
        $clientId = $request->query('clientId');

        if($keyNumberId && $clientId) {
            $url = "/cad/clientId/$clientId/id/{$keyNumberModel->getTvcId()}";
            $this->set('locationUrl', $url);
            $this->set('status_code', 201);
        } else {
            throw new ConflictException("Key Number already exists");
        }

    }

    /**
     * @param Request $request
     * @throws NotFoundException
     * @throws \Elf\Exception\MalformedException
     * @throws \Exception
     */
    public function handlePatch(Request $request)
    {
        $documentService = $this->app->service('pdfDocumentGeneration');
        $id = $request->query('id');
        $action = $request->query('action');
        $sendWithdrawnEmail = false;
        if(null !== $id) {
            $inputData = $request->retrieveJSONInput();

            $keyNumberModel = $this->app->model('KeyNumber');

            $keyNumberModel->getTvcById($id);

            if($action == 'manualExpiry') {
                if(!empty($inputData['expiryTargets'])) {
                    $keyNumberModel->manuallyExpireKeyNumbers($inputData['expiryTargets']);
                    $this->set('status_code',204);
                    return;
                } else {
                    throw new \InvalidArgumentException(['displayMessage' => 'Unable to expire - invalid TVCs']);
                }
            }
			
			if($action == 'manualExtendExpiry') {
                if(!empty($inputData['extendedExpiryTargets'])) {
					foreach($inputData['extendedExpiryTargets'] as $key=>$tvcNumber) {
						$keyNumberModel->manuallyExtendExpiryByKeyNumbers($tvcNumber);
					}
                    $this->set('status_code',204);
                    return;
                } else {
                    throw new \InvalidArgumentException(['displayMessage' => 'Unable to extend expiry - invalid TVCs']);
                }
            }

            if(!empty($inputData['eventType']) && $inputData['eventType'] == 'W') {

				
			
                $keyNumberData = $keyNumberModel->getAsArray();

                // The check below ensures that the key number is about to be withdrawn
                if($keyNumberData['eventType'] != 'W') {
                    $sendWithdrawnEmail = true;

                    // set withdrawn date
                    $now = new \DateTime();
                    $inputData['withdrawnDate'] = $now->format('Y-m-d H:i:s');

                    // Set DAR fields
                    $inputData['tvcDAREvent'] = \App\Models\KeyNumber::STATUS_WITHDRAWN;
                    $inputData['tvcDAREventDate'] = $inputData['withdrawnDate'];
                }
            }

            // If an agency user is attempting to change a key number to be revised after the fact, they must submit
            // all the fields checked below, or an exception will be thrown
            if(!empty($inputData['originalTvcId'])) {
                $jobRevision = $this->app->Model('JobRevision');
                $originalFields = $jobRevision->validateKeyNumberForRevisions($inputData);
                $inputData = array_merge($inputData,$originalFields);
            }
            $keyNumberModel->setFromArray($inputData);
            if(!$keyNumberModel->validate($keyNumberModel->getAsArray(true),array('required'))){
                $this->set('status_code', 400);
                return $keyNumberModel->getErrors();
            }
            $keyNumberModel->load();

            $inputData['jobId'] = $keyNumberModel->getJobId();

            $keyNumberModel->setFromArray($inputData);
            if($keyNumberModel->save()) {
                $this->set('status_code',204);
                if($sendWithdrawnEmail === true) {
                    $scriptExecutable = "php " . ASYNC_SCRIPT. " AsyncNotificationSendout "
                        ."--ENVIRONMENT=".getenv("ENVIRONMENT") . " "
                        ."--notificationType="."withdrawnCadNumber "
                        ."--tvcId=".$id;
                    $this->requestAsync($scriptExecutable);
                }
                return;
            }
        } else {
            throw new NotFoundException(['displayMessage' => 'Unable to update - please specify a TVC id']);
        }
    }

    /**
     * delete it yo!
     * @param Request $request
     * @throws NotFoundException
     */
    public function handleDelete(Request $request)
    {
        if($id = $request->query('id')) {
            $keyNumberModel = $this->app->model('KeyNumber');
            $keyNumberModel->setTvcId($id);
            if($keyNumberModel->deleteTvc()) {
                $this->set('status_code', 204);
                return;
            } else {
                throw new NotFoundException(['displayMessage' => 'Unable to update - please specify a TVC id']);
            }
        } else {
            throw new NotFoundException(['displayMessage' => 'Unable to update - please specify a TVC id']);
        }
    }

}
