<?php

/**
 * This is the interface between the job checklist and all the sub-api's required to successfully confirm a final order
 */

namespace App\Controllers;

use App\Services\JobPayment;
use Elf\Exception\ConflictException;
use Elf\Exception\NotFoundException;
use Elf\Http\Request;
use Elf\Event\RestEvent;

class JobCheckList extends AppRestController
{
    /**
     * 1. Process the checklist
     * 2. Check for action
     * 3. Confirm and assign cad numbers
     * 4. Create final order form (if all key numbers are processed)
     * 5. Generate documents
     * 6. Send documents
     * 7. Return success
     *
     * @param Request $request
     * @return mixed
     * @throws NotFoundException
     *
     * Endpoint to run this (All requests to the API go through the CAD system):
     * https://cad-beta.freetv.com.au/AjaxCalls/calltype/JobChecklist/action/confirm
     */
    public function handlePost(Request $request)
    {
        // Start logging
        $loggingService = $this->app->service('Logger');
        $loggingService->setFilename('ConfirmFinalOrderForm');
        $loggingService->info('jobCheckList::handlePost');

        $attachmentLinks = array();
        $inputData = $request->retrieveJSONInput();
        $action = $request->query('action');
        $override = false;

        /** @var \App\Models\JobChecklist $jobChecklistModel */
        $jobChecklistModel = $this->app->model('JobChecklist');

        /** @var JobPayment $jobPaymentService */
        $jobPaymentService = $this->app->service('JobPayment');
        $jobPaymentService->setJobId($inputData['jobId']);

        $jobModel = $this->app->model('job');
        $jobModel->setJobId($inputData['jobId']);
        $jobModel->load();
        $jobData = $jobModel->getFullJob();

        $loggingService->info('jobCheckList::handlePost Starting processing Job ID ' . $inputData['jobId']);

        if ( $jobChecklistModel->processChecklist($inputData) ) {
            try {
                // If the user chose to save the form
                if ($action == 'save') {
                    $loggingService->info('Saved job ' . $inputData['jobId']);
                    $this->set('status_code', 201);
                    return;
                }

                $documentService = $this->app->service('pdfDocumentGeneration');

                // If you choose to override and assign cad numbers
                if ($action == 'override') {
                    $override = true;
                    $jobPaymentService->setOverride($override);
                }

                $params = array();
                //get all valid key numbers
                $validKeyNumbers = $jobChecklistModel->getValidKeyNumbers($override);

                if (!$validKeyNumbers) {
                    $loggingService->error('jobCheckList::handlePost No valid key numbers.');
                } else {
                    $loggingService->info('jobCheckList::handlePost Valid key numbers: ' . json_encode($validKeyNumbers));
                }

                $params['validKeyNumbers'] = $validKeyNumbers;

                if (!empty($inputData['manualAdj'])) {
                    //check for manual adjustments if they exist
                    $params['manualAdjustments'] = $inputData['manualAdj'];
                }

                //generate invoice and upload invoice document
                // $result is an array containing some invoice model data. The important thing is that it contains
                // the key 'id' so you can lookup the invoice if more data is required.
                $result = $jobPaymentService->process($params);
                $loggingService->info('jobCheckList::handlePost Generated invoice: ' . json_encode($result));

                // Update amounts in TVCs table
                $jobPaymentService->updateTVCAmounts($jobPaymentService->getItemsArray());
                $loggingService->info('jobCheckList::handlePost Updated TVC Amounts');

                // Store instantiated jobPayment model into jobChecklist to re-use values
                $jobChecklistModel->setJobPaymentModel($jobPaymentService);
                $loggingService->info('jobCheckList::handlePost Set Job Payment Model');

                //Process the transaction depending on the agency account type ACC or COD or override
                $loggingService->info('jobCheckList::handlePost Confirming final order');
                $transactionData = $jobChecklistModel->confirmFinalOrder($result, $override);
                $loggingService->info('jobCheckList::handlePost Confirmed final order form. Transaction data: ' . json_encode($transactionData));

                //if the confirm final order completed successfully
                //take the transaction data and update the invoice
                $jobPaymentService->setTransactionId($transactionData['transactionId']);
                $loggingService->info('jobCheckList::handlePost Set transaction ID: ' . $transactionData['transactionId']);

                //at this point generate the document and update the invoice, check the transaction for fail cases
                $failedInvoice = false;
                if ($transactionData['failed'] == true) {
                    $loggingService->error('jobCheckList::handlePost Failed transaction');
                    $failedInvoice = true;
                }

                $documentData = $jobPaymentService->updateInvoice($result['id'], $failedInvoice);
                $loggingService->info('jobCheckList::handlePost Updated invoice. Document data: ' . json_encode($documentData));

                if ($failedInvoice == true) {
                    $loggingService->error('jobCheckList::handlePost Failed invoice. Sending notification');

                    $scriptExecutable = "php " . ASYNC_SCRIPT . " AsyncNotificationSendout "
                        . " --ENVIRONMENT=" . getenv("ENVIRONMENT") . " "
                        . " --notificationType=paymentFailed"
                        . " --jobId=" . $inputData['jobId'];
                    $scriptOutput = $this->requestAsync($scriptExecutable);
                    $loggingService->info('jobCheckList::handlePost Notification output: ' . $scriptOutput);

                    //return 200 to make the ajax successful, but on the front end it will display an error for failed payment.
                    $this->set('status_code', 200);

                    // Westpac transaction error
                    if (isset($transactionData['westpacTransactionError'])) {
                        $loggingService->error('jobCheckList::handlePost Westpac transaction error');
                        return ['westpacTransactionError' => true];
                    } else {
                        $loggingService->error('jobCheckList::handlePost Transaction error');
                        return;
                    }
                }


                $jobChecklistModel->setTransactionId($transactionData['transactionId']);
                $loggingService->info('jobCheckList::handlePost Set transaction ID: ' . $transactionData['transactionId']);

                //link the valid key numbers to the transaction id
                $this->updateTvcTransactionId($validKeyNumbers, $transactionData['transactionId']);
                $loggingService->info('jobCheckList::handlePost Updated Tvc Transaction Id: ' . $transactionData['transactionId']);

                //mark manualAdjustments as processed and map them to a transaction id
                $manualAdjs = $jobChecklistModel->getManualAdjustments();

                if (!empty($manualAdjs)) {
                    $jobChecklistModel->updateExistingManualAdjustments($manualAdjs);
                    $loggingService->info('jobCheckList::handlePost Updated manual adjustments: ' . json_encode($manualAdjs));
                }

                $documentService->setFileName('Cad_Advice_Slip-' . $inputData['jobId']);
                $loggingService->info('jobCheckList::handlePost Set filename for CAD Advice slip: ' . 'Cad_Advice_Slip-' . $inputData['jobId']);

                //Checks if all key numbers have been processed
                if ($jobChecklistModel->areAllKeyNumbersProcessed()) {
                    $loggingService->info('jobCheckList::handlePost All key numbers have been processed');

                    // Create and store the blob
                    $returnedIds = $jobChecklistModel->createFinalOrderForm();
                    $loggingService->info('jobCheckList::handlePost Created final order form: ' . json_encode($returnedIds));

                    if (!empty($returnedIds)) {
                        $jobPaymentService->setFinalOrderFormId($returnedIds['orderFormId']);
                        $loggingService->info('jobCheckList::handlePost Set Final Order Form Id: ' . $returnedIds['orderFormId']);

                        $scriptExecutable = "php " . ASYNC_SCRIPT . " AsyncNotificationSendout "
                            . "--ENVIRONMENT=" . getenv("ENVIRONMENT") . " "
                            . " --notificationType=finalOrderForm"
                            . " --jobId=" . $inputData['jobId']
                            . " --attachmentPaths=" . json_encode(array($returnedIds['documentId']));
                        $scriptOutput = $this->requestAsync($scriptExecutable);
                        $loggingService->info('jobCheckList::handlePost Notification output: ' . $scriptOutput);
                    } else {
                        $loggingService->error('jobCheckList::handlePost Problem generating final order form');
                        $params['validKeyNumbers'] = $jobChecklistModel->getValidKeyNumbers();
                        if (!empty($inputData['manualAdj'])) {
                            $params['manualAdjustments'] = $inputData['manualAdj'];
                        }
                    }
                }

                //reset the attachments so we don't attach too much
                $attachmentLinks = array();

                // If the job is not a pre-check, make a CAD Advice slip
                if (!$jobModel->isPrecheck()) {
                    $loggingService->info('jobCheckList::handlePost Generating CAD Advice slip');

                    $adviceSlip = $jobChecklistModel->createAdviceSlip();
                    $loggingService->info('jobCheckList::handlePost Generated CAD Advice slip: ' . json_encode($adviceSlip));

                    //reset the attachments so we don't attach too much
                    $attachmentLinks[] = $adviceSlip['documentId'];
                }

                $attachmentLinks[] = $documentData['documentId'];

                // Send documents here
                $loggingService->info('jobCheckList::handlePost Sending CAD Issued notification');
                $scriptExecutable = "php " . ASYNC_SCRIPT . " AsyncNotificationSendout "
                    . " --ENVIRONMENT=" . getenv("ENVIRONMENT") . " "
                    . " --notificationType=CADIssued"
                    . " --jobId=" . $inputData['jobId']
                    . " --attachmentPaths=" . json_encode($attachmentLinks);
                $scriptOutput = $this->requestAsync($scriptExecutable);
                $loggingService->info('jobCheckList::handlePost CAD Issued notification sent. Notification output: ' . $scriptOutput);

                if ($override == true) {
                    //set stop credit on the agency to Manual Override Failed Payment
                    $loggingService->info('jobCheckList::handlePost Set stop credit on the agency to Manual Override Failed Payment');

                    $agencyModel = $this->app->model('agency');
                    $agencyModel->setAgencyId($jobData['agency']['agencyId']);
                    $agencyModel->setAgencyStopCreditFailedTransaction();
                }

                $this->set('status_code', 201);
                $loggingService->info('jobCheckList::handlePost Finished processing job ' . $inputData['jobId']);
                return;
            } catch ( \Exception $e ) {
                $loggingService->error('jobCheckList::handlePost Exception: ' . $e->getMessage());
                $this->set('status_code', 400);
                throw $e;
            }
        }
        $loggingService->error('No results');
        throw new NotFoundException("No results");
    }

    /**
     * update tvc table by inserting transaction id
     * @param type $validKeyNumbers
     * @param type $transactionId
     */
    private function updateTvcTransactionId($validKeyNumbers, $transactionId)
    {
        $keyNumbers = array_keys($validKeyNumbers);

        $keyNumberModel = $this->app->model('keyNumber');
        $keyNumberModel->setTransactionId($transactionId);
        $keyNumberModel->updateTvcTransactionId($keyNumbers);
    }

    private function formatInputArray($unencodedObjects){
        $keyNumbers = array();
        $manualAdjustmentsNew= array();
        $manualAdjustmentsExisting= array();

        foreach($unencodedObjects as $key => $objects){
            $output_array = array();
            preg_match("/(\w*)\[(\w*\d*)\](?:\[(\d*)\])?/", $key, $output_array);
            if(isset($output_array[1])){
                if($output_array[1] == 'keyNumber'){
                    $keyNumbers[$output_array[2]] = $objects;
                }
                elseif($output_array[1] == 'manualAdj'){
                    if($output_array[2] == 'existing'){
                        $manualAdjustmentsExisting[$output_array[3]] = $objects;
                    }
                    elseif($output_array[2] == 'new'){
                        $manualAdjustmentsNew[] = $objects;
                    }
                    else{
                        throw new \Exception("Invalid input data: manual adjustment array invalid");
                    }
                }
            }
        }
        $temp_array = array(
            'jobId'=> $unencodedObjects['jobId'],
            'keyNumber' => $keyNumbers,
            'manualAdj' => array(
                'new' => $manualAdjustmentsNew,
                'existing' => $manualAdjustmentsExisting,
            ),
        );

        return $temp_array;
    }

}
