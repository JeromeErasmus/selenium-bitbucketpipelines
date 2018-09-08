<?php

namespace App\Controllers;
use Elf\Exception\ConflictException;
use Elf\Exception\NotFoundException;
use Elf\Http\Request;
use Elf\Event\RestEvent;

/**
 * Description of ManualInvoice
 *
 * @author luca.confalonieri
 */
class ManualInvoice extends AppRestController
{
    /**
     *
     * @param Request $request
     * @return mixed
     * @throws NotFoundException
     */
    public function handlePost(Request $request)
    {
        // Start logging
        $loggingService = $this->app->service('Logger');
        $loggingService->setFilename('ConfirmFinalOrderForm');
        $loggingService->info('ManualInvoice::handlePost');

        $inputData = $request->retrieveJSONInput();

        $action = $request->query('action');

        if($action == 'submit') {

            $jobPaymentService = $this->app->service('JobPayment');
            $jobPaymentService->setJobId($inputData['jobId']);
            $result = $jobPaymentService->process(array('manualInvoiceType' => '')); //create the invoice record

            //try to run a transaction
            $manualInvoiceService = $this->app->service('ManualInvoice');
            $manualInvoiceService->setManualInvoiceType($inputData['manualInvoiceType']);

            $inputData['orderNumber'] = $result['id'];
            $inputData['jobReferenceNo'] = $inputData['jobId'];
            $inputData['originalOrderNumber'] =  $result['id']; // the original order number is the invoice id
            $inputData['invoiceId'] =  $result['id'];

            $loggingService->info('jobCheckList::handlePost Processing transaction. Input data: ' . json_encode($inputData));
            $transactionData = $manualInvoiceService->transaction($inputData);
            $loggingService->info('jobCheckList::handlePost Transaction processed. Transaction data: ' . json_encode($transactionData));

            //if transaction is successful, generate the invoice
            $failedInvoice = false;
            if ($transactionData['failed'] == true || empty($transactionData['transactionId'])) {
                $failedInvoice = true;
            }
            $jobPaymentService->setTransactionId($transactionData['transactionId']);

            $documentData = $jobPaymentService->processManualInvoice($inputData, $failedInvoice);
            $loggingService->info('jobCheckList::handlePost Processed Manual Invoice. Document data: ' . json_encode($documentData));

            $documentData = $jobPaymentService->updateInvoice($result['id'], $failedInvoice);
            $loggingService->info('ManualInvoice::handlePost Updated invoice. Document data: ' . json_encode($documentData));

            //update the invoice with the price breakdown
            $inputData['invoiceId'] = $result['id'];

            if ($failedInvoice !== true) {
                //send said invoices generated above
                $attachmentLinks[] = $documentData['documentId'];
                // Send documents here
                $scriptExecutable = "php " . ASYNC_SCRIPT . " AsyncNotificationSendout "
                    . "--ENVIRONMENT=".getenv("ENVIRONMENT") . " "
                    . " --notificationType=additionalCharge"
                    . " --jobId=" . $inputData['jobId']
                    . " --attachmentPaths=" . json_encode($attachmentLinks);
                $this->requestAsync($scriptExecutable);
                $loggingService->info('ManualInvoice::handlePost Sent notification');
            }
            $this->set('status_code', 201);
            return $result;
        }
        else {
            throw new NotFoundException(['displayMessage' => 'This is not a valid end point']);
        }
    }
}