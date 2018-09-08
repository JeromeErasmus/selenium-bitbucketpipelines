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
use App\Models\DocumentUploadType;

class Document extends AppRestController{

    /**
     * 
     * @param Request $request
     * @return mixed
     * @throws MalformedException
     */
    public function handleGet(Request $request)
    {          
        //call load
        $documentModel = $this->app->model('Document');
        
        $jobId = $request->query('id');
        $uploadType = $request->query('uploadType');
        $documentId = $request->query('documentId');
        $keyNumberId = $request->query('keyNumberId');
        $requirementId = $request->query('requirementId');
        $order = $request->query('order');

        //when you retrieve a requirements document, we require jobId, keyNumberId and requirementId to be set

        //if jobId is empty then fail
        // not empty key number id and job id is empty then fail
        // not empty requirement id and keynumber is empty then fail
        if ( (empty($jobId) && empty($documentId) ) ||
            (!empty($keyNumberId) && empty($jobId) ) ||
                (!empty($requirementId) && empty($keyNumberId) )
        ){
            throw new MalformedException("Malformed request URL, one or more expected fields has not been provided");
        }

        //if a specific document is requested
        if($documentId){
            $documentModel->setDocumentId($documentId);
            $documentData = $documentModel->load();

            $documentUpload = $this->app->service('documentUpload');

            //returns an assoc array of always one element
            return $documentUpload->documentSendFile($documentData[0]);
        }
        else{
            $documentModel->setJobId($jobId);
            if (!empty($requirementId) ){
                $documentModel->setRequirementId($requirementId);
            }
            if (!empty($keyNumberId) ){
                $documentModel->setKeyNumberId($keyNumberId);
            }
            if (!empty($order) ){
                $documentModel->setOrder($order);
            }
            $documentModel->setRetrieveDeleted(false);
            $documentModel->setUploadType($uploadType);
        }
        return $documentModel->load();
    }

    /**
     * 
     * @param Request $request
     */
    public function handlePatch(Request $request)
    {
        $this->set('status_code', 404);
    }
    
    /**
     * 
     * @param Request $request
     */
    public function handleDelete(Request $request)
    {
        $this->set('status_code', 404);
    }
    
    /**
     *
     * @param Request $request
     * @return type
     * @throws MalformedException
     */
    public function handlePost(Request $request)
    {
        try {
            $inputData = $request->retrieveJSONInput();

            if ( (empty($inputData['jobId']) ) ||
                (!empty($inputData['keyNumberId']) && empty($inputData['jobId']) ) ||
                (!empty($inputData['requirementId']) && empty($inputData['keyNumberId']) )
            ){
                throw new MalformedException("Request Parameters are invalid");
            }

            $inputData['userId'] = $this->app->service('user')->getCurrentUser()->getUserSysid();
            $inputData['systemFilename'] = $inputData['fileName'];

            $documentUpload = $this->app->service('documentUpload');

            //create the filepath for saving
            $folder = $documentUpload->getFolderPath();

            $documentModel = $this->app->model('Document');
            $documentModel->setFields($inputData);

            if(!$documentModel->validate($documentModel->getAsArray(true))) {
                $this->set('status_code', 400);
                return $documentModel->getErrors();
            }

            if (empty($inputData['fileContents'])) {
                throw new MalformedException("fileContents field not sent");    //not part of field map as not a db entry
            }

            if(isset($inputData['fileIsS3Link']) && $inputData['fileIsS3Link'] == true) {
                $inputData['systemFilename'] = $inputData['fileContents'];
                $documentModel->setFields($inputData);
                $documentId = $documentModel->save();
            } else {

                $date = new \DateTime();

                $inputData['systemFileName'] = $date->format("YmdHis") . "-" . $inputData['fileName'];

                $documentModel->setFields($inputData);

                $file = $documentUpload->documentCreateFile($inputData);
   
                $inputData['systemFilename'] = $file['url'];
                if(empty($file['local'])) {
                    $inputData['fileIsS3Link'] = true;
                }
                
                $documentModel->setFields($inputData); // reset the new fields after create the file.

                $documentId = $documentModel->save();

            }

            $this->set('status_code', 201);
            $this->set('locationUrl', 'cad/document/documentId/' . $documentId);
            return ;
        } catch (MalformedException $e) {
            $this->set('status_code', 400);
            return (array('code' => 400, 'message' => $e->getDebugMessage()));

        } catch (ConflictException $e ) {
            $this->set('status_code', 409);
            return (array('code' => 409, 'message' => $e->getMessage()));
        } catch (\Exception $exception) {
             $this->set('status_code', 406);
            return (array('code' => 406, 'message' => $exception->getMessage()));
        }        
    }
}
