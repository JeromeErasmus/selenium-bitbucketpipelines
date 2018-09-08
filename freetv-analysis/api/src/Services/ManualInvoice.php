<?php

namespace App\Services;

use Elf\Core\Module;
use App\Models\Country;

class ManualInvoice extends Module
{

    private $job;
    private $jobData;
    private $manualInvoiceType;
    private $manualInvoiceAgencyTransactionType;

    /**
     * Service entry point
     * This function takes all the data that is available to process an invoice
     *
     * {
    "jobId" : 1041243,
    "manualInvoiceType": "credit",
    "invoiceAmount": 123,
    "invoiceComment": "more money to pay"
    }
     *
     * @param $invoiceData
     * @return array
     */
    public function transaction($invoiceData)
    {
        $this->job = $invoiceData['jobId'];
        $this->jobData = $this->getFullJob();
        $jobPaymentService = $this->app->service('jobPayment');

        $accountType = $this->jobData['agency']['accountType'];
        $agencyId = $this->jobData['agency']['agencyId'];

        $gstExemption = $jobPaymentService->showGst($this->jobData['agency']);

        $splitAmounts = $jobPaymentService->splitAmounts($invoiceData['invoiceAmount'],$gstExemption);

        $invoiceData['amountIncGst'] = $splitAmounts['gst'] + $splitAmounts['item_amount_ex_gst'];
        $invoiceData['amount'] = bcmul($invoiceData['amountIncGst'],100,0);
        $invoiceData['amountExGst'] = $splitAmounts['item_amount_ex_gst'];
        $invoiceData['gst'] = $splitAmounts['gst'];
        $invoiceData['jobReferenceNo'] = $invoiceData['jobId'];
        switch($this->manualInvoiceType){
            case 'additionalCharge':
                //create an additional charge to the account/token provided
                $transactionData = $this->processAdditionalCharge($agencyId, $accountType, $invoiceData);
                break;
            case 'credit':
                //add credit to the account/token provided
                $transactionData = $this->processCreditToAgency($agencyId, $accountType, $invoiceData);
                break;
        }

        return $transactionData;
    }

    /**
     * Processes an additional charge based on the account type that is nominated for the agency
     *
     * @param $agencyId
     * @param $accountType
     * @param $invoiceData
     * @return array
     */
    public function processAdditionalCharge($agencyId, $accountType, $invoiceData)
    {
        switch($accountType){
            case AGENCY_ACCOUNT_TYPE_ACC:
                return $this->processAccountTransaction($agencyId, $invoiceData);
            case AGENCY_ACCOUNT_TYPE_COD:
                return $this->processCreditTransaction($agencyId, $invoiceData);
        }
    }

    /**
     * Processes a refund based on the account type that is nominated for the agency
     *
     * @param $agencyId
     * @param $accountType
     * @param $invoiceData
     * @return array
     */
    public function processCreditToAgency($agencyId, $accountType, $invoiceData)
    {

        $refund = true;

        switch($accountType){
            case 'ACC':
                return $this->processAccountTransaction($agencyId, $invoiceData, $refund);
            case 'COD':
                return $this->processCreditTransaction($agencyId, $invoiceData, $refund);
        }
    }

    /**
     * Creates a transaction record
     * Returns transaction data
     *
     * @param $agencyId
     * @param $invoiceData
     * @param bool $refund
     * @return array
     */
    public function processAccountTransaction($agencyId, $invoiceData, $refund = false)
    {
        if($refund === true){
            $invoiceData['type'] = 'refund';
            unset($invoiceData['authId']);
        }
        $westpacService = $this->app->service('westpacProcessing');

        $transaction = $westpacService->processAccTransaction($agencyId, $invoiceData);

        $transactionData['transactionId'] = $transaction['id'];
        return $transactionData;

    }

    public function processCreditTransaction($agencyId, $invoiceData, $refund = false)
    {
        $westpacService = $this->app->service('westpacProcessing');

        //this formats the transaction as a refund call to the qvalent API
        if($refund === true){
            $invoiceData['type'] = 'refund';
            unset($invoiceData['authId']);
        }

        $transactionData = $westpacService->processTransaction($agencyId, $invoiceData);

        $transactionData['transactionId'] = $transactionData['id'];
        return $transactionData;

    }

    public function setManualInvoiceType($manualInvoiceType)
    {
        $this->manualInvoiceType = $manualInvoiceType;
    }

    public function getManualInvoiceType()
    {
        return $this->manualInvoiceType;
    }

    /**
     * @return mixed
     */
    public function getManualInvoiceAgencyTransactionType()
    {
        return $this->manualInvoiceAgencyTransactionType;
    }

    /**
     * @param mixed $manualInvoiceAgencyTransactionType
     */
    public function setManualInvoiceAgencyTransactionType($manualInvoiceAgencyTransactionType)
    {
        $this->manualInvoiceAgencyTransactionType = $manualInvoiceAgencyTransactionType;
    }

    private function getFullJob()
    {
        $job = $this->app->model('job');
        $job->setJobId($this->job);
        $job->load();
        return $job->getFullJob();
    }


}
