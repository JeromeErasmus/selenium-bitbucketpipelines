<?php
/**
 * Created by PhpStorm.
 * User: jeremy.paul
 * Upon receiving a final order form checklist, breaks it up into it's relevant sections (TVCs and Manual adjustments) and carries out CRUD operations
 * Date: 27/01/2016
 * Time: 10:48 AM
 */

namespace App\Models;


use App\Services\JobPayment;
use Elf\Db\AbstractAction;
use Elf\Exception\NotFoundException;
use App\Models\ManualAdjustment as Model;
use App\Models\OrderForm;
use App\Models\AuditLog as AuditLog;
use App\Models\JobAuditLog as JobAuditLog;
use App\Models\Invoice as Invoice;

class JobChecklist extends AbstractAction{

    private $jobId;
    private $now;
    private $keyNumbers;
    private $transactionId;
    private $orderFormTypes;

    private $validKeyNumbers;
    private $manualAdjustments;
    private $jobData;

    public $finalOrderType;
    public $editActionType;
    public $createActionType;
    public $confirmActionType = 4;

    private $jobPaymentServiceModel = '';

    // Logger
    private $loggingService;


    public function __construct($app) {
        parent::__construct($app);

        // Start Logger
        $this->loggingService = $this->app->service('Logger');

        $this->orderFormTypes = $this->app->config->get('orderFormTypes');
        $this->httpLogConstants = $this->app->config->get('httpLogConstants');

        $this->finalOrderType = $this->orderFormTypes['Final'];

        $this->editActionType = isset($this->httpLogConstants['actionTypes']['PATCH']) ? $this->httpLogConstants['actionTypes']['PATCH'] : array();
        $this->createActionType = isset($this->httpLogConstants['Initial']['POST']) ? $this->httpLogConstants['Initial']['POST'] : array();

        return $this; // for method chaining
    }

    public function save()
    {

    }

    public function load()
    {

    }

    /**
     * Separates the input checklist data into it's constituents (TVCS/Keynumbers and manual adjustments)
     *
     * @param $checklistData
     * @return bool|string
     */
    public function processChecklist($checklistData)
    {
        // Start logging
        $loggingService = $this->app->service('Logger');
        $loggingService->setFilename('ConfirmFinalOrderForm');
        $loggingService->info('JobChecklist::processChecklist');

        // Job Id is required for all of these
        if (!isset($checklistData['jobId'])) {
            return false;
        }

        $this->jobId = $checklistData['jobId'];
        $this->now = new \DateTime();
        $this->now = $this->now->format('Y-m-d H:i:s');
        try {
            if(isset($checklistData['keyNumber'])){
                $keyNumbers = $checklistData['keyNumber'];
                $loggingService->info('JobChecklist::processChecklist Patching key numbers');
                $this->patchKeyNumbers($keyNumbers);
            }

            if(!empty($checklistData['manualAdj']) && isset($checklistData['manualAdj']['new'])) {
                $newManualAdjustment = $checklistData['manualAdj']['new'];
                $loggingService->info('JobChecklist::processChecklist Creating new manual adjustments');
                $this->createNewManualAdjustments($newManualAdjustment);
            }
            if(!empty($checklistData['manualAdj']) && isset($checklistData['manualAdj']['existing'])) {
                $existingManualAdjustments = $checklistData['manualAdj']['existing'];
                $loggingService->info('JobChecklist::processChecklist Updating existing manual adjustments');
                $this->updateExistingManualAdjustments($existingManualAdjustments);
            }
            $loggingService->info('JobChecklist::processChecklist Creating new time line entry');
            $this->createTimelineEntry($checklistData);
            return true;
        } catch (\Exception $e) {
            $loggingService->error('JobChecklist::processChecklist Exception: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Sequentially goes through key numbers and updates them
     *
     * @param $keyNumbers
     */
    public function patchKeyNumbers($keyNumbers)
    {
        $this->loggingService->setFilename('ConfirmFinalOrderForm');

        $this->keyNumbers = $keyNumbers;
        foreach ($keyNumbers as $tvcId => $keyNumber) {
            //always create a new model within the loop so we start with a 'clean' keyNumber, 
            //no values are carried over from previous key number
            $keyNumberModel = $this->app->model('KeyNumber');

            $keyNumberModel->setTvcId($tvcId);
            $this->loggingService->info('JobChecklist::patchKeyNumbers Loading TVC: ' . $tvcId . ' BEFORE');
            $keyNumberModel->load();
            $this->loggingService->info('JobChecklist::patchKeyNumbers Loading TVC ' . $tvcId . ' AFTER');
            $keyNumberModel->setFromArray($keyNumber);

            if(!$keyNumberModel->validate($keyNumberModel->getAsArray(true))){
                return $keyNumberModel->getErrors();
            }
            $keyNumberModel->save();
        }
    }

    /**
     * Sequentially creates new manual adjustments
     *
     * @param $newManualAdjustments
     */
    public function createNewManualAdjustments($newManualAdjustments)
    {
        $model = new Model();
        $capsule = $this->app->service('eloquent')->getCapsule();
        foreach($newManualAdjustments as $newAdjustment) {
            $newAdjustment['createdAt'] = $this->now;
            $newAdjustment['jobId'] = $this->jobId;
            $model->setFromArray($newAdjustment);

            if($model->validate()) {
                $model->save();
                $timelineEntry = $this->formatTimelineData($newAdjustment,$model->id,'manualAdjustment',$this->createActionType);
                $this->createTimelineEntry($timelineEntry);

                $this->manualAdjustments[$model->id] = $model->getAsArray();
            }
        }
    }

    /**
     * Sequentially updates existing adjustments
     *
     * @param $existingManualAdjustments
     */
    public function updateExistingManualAdjustments($existingManualAdjustments)
    {
        $capsule = $this->app->service('eloquent')->getCapsule();

        foreach($existingManualAdjustments as $id => $existingAdjustment) {
            $existingAdjustment['updatedAt'] = $this->now;
            $existingAdjustment['jobId'] = $this->jobId;

            // if we have a transaction id we update the manual transaction and mark it as processed
            if(!empty($this->transactionId)){
                $existingAdjustment['transactionId'] = $this->transactionId;
                $existingAdjustment['processed'] = '1';
            }

            $model = Model::find($id);
            $model->setFromArray($existingAdjustment);

            if($model->validate()) {
                $model->save();
                $timelineEntry = $this->formatTimelineData($existingAdjustment,$id,'manualAdjustment',$this->editActionType);
                $this->createTimelineEntry($timelineEntry);
                $this->manualAdjustments[$id] = $model->getAsArray();
            }
        }
    }

    public function createAdviceSlip()
    {
        $adviceSlipData = $this->compileAdviceSlipData();
        $documentService = $this->app->service('pdfDocumentGeneration');
        $documentService->setFileName('Cad_Advice_Slip-' . $this->jobId);
        $adviceSlip = $documentService->generateDocument('cadAdviceSlip', $adviceSlipData);
        return $adviceSlip;
    }

    /**
     * This creates an entry in the order form table with the type of final order,
     * then logs a timeline entry to say that the order has been confirmed
     *
     * @return type|bool
     */
    public function createFinalOrderForm()
    {
        $model = new OrderForm();
        $capsule = $this->app->service('eloquent')->getCapsule();

        //here I need to get all the data
        $finalOrderFormData =  $this->compileFinalOrderFormData($this->jobId);

        $documentService = $this->app->service('pdfDocumentGeneration');
        $documentService->setFileName('Final_Order_Form-' . $this->jobId);
        $documentThings = $documentService->generateDocument('originalOrderForm',$finalOrderFormData);

        $data['jobData'] = $this->jobData;
        $data['keyNumbers'] = $this->validKeyNumbers;
        $data['manualAdjustments'] =  $this->manualAdjustments;
        $data['documentSpecificData'] =  $finalOrderFormData;

        $inputData = array (
            'jobId' => $this->jobId,
            'type' => $this->finalOrderType,
            'inputData' => json_encode($finalOrderFormData),
            'createdAt' => $this->now,
            'createdBy' => $this->app->service('user')->getCurrentUser()->getUserSysid(),
        );
        $model->setFromArray($inputData);

        if($model->validate()) {
            $model->save();
            $timelineEntry = $this->formatTimelineData($inputData,$model->id,'initialOrder',$this->confirmActionType);
            $this->createTimelineEntry($timelineEntry);
            return
                array(
                    'orderFormId' => $model->id,
                    'documentId' => $documentThings['documentId'],
                );
        }
        return false;
    }

    /**
     * This function formats data from the various functions to be entered into the timeline
     *
     * @param $requestData
     * @param $assocId
     * @param $logType
     * @param $actionType
     * @return array
     *
     */
    public function formatTimelineData($requestData, $assocId, $logType,$actionType) {
        $timelineData = array();
        $timelineData['assocId'] = $assocId;
        $capsule = $this->app->service('eloquent')->getCapsule();

        $logTypes = LogTypes::where('route_name', $logType)->get();

        foreach ($logTypes as $log) {
            $timelineData['assocType'] = $log->id;
        }

        $timelineData['actionType'] = $actionType;
        $timelineData['request'] = json_encode($requestData);
        $timelineData['createdBy'] = $this->app->service('user')->getCurrentUser()->getUserSysid();
        $timelineData['dateAndTime'] = $this->now;

        return $timelineData;
    }

    /**
     * This function creates a timeline entry using the supplied $relevantData
     *
     * @param $relevantData
     */
    public function createTimelineEntry($relevantData)
    {
        $capsule = $this->app->service('eloquent')->getCapsule();
        $auditLog = new AuditLog();
        $jobAuditLog = new JobAuditLog();
        $auditLog->setFromArray($relevantData);

        if ($auditLog->validate()) {
            $auditLog->save();
            $data = array(
                'auditLogId' => $auditLog->id,
                'jobId' => $this->jobId,
            );

            $jobAuditLog->setFromArray($data);
            if ($jobAuditLog->validate()) {
                $jobAuditLog->save();
            }
            return;
        }
    }

    /**
     * This processes the transaction for all valid key numbers
     *
     * If the agency is to be charged, a transaction will process via their nominated primary westpac token
     *
     * @param $invoiceData
     * @param $override
     * @return array|void
     */
    public function confirmFinalOrder($invoiceData, $override = false)
    {
        // Start logging
        $loggingService = $this->app->service('Logger');
        $loggingService->setFilename('ConfirmFinalOrderForm');
        $loggingService->info('JobChecklist::confirmFinalOrder');

        // Given the job id, determine what type of account customer the agency is
        $this->jobData = $this->getFullJob();

        $this->retrieveValidatedKeyNumbers();
        //if there are no key number
        if(empty($this->validKeyNumbers)){
            return false;
        }

        if ($override === true) {
            $loggingService->info('JobChecklist::confirmFinalOrder Process Override Transaction');
            return $this->processOverrideTransaction($invoiceData);
        }

        $accountType = $this->jobData['agency']['accountType'];

        switch ($accountType) {
            case AGENCY_ACCOUNT_TYPE_ACC :
                $loggingService->info('JobChecklist::confirmFinalOrder Process Account Transaction');
                return $this->processAccountTransaction($invoiceData);
            case AGENCY_ACCOUNT_TYPE_COD :
                $loggingService->info('JobChecklist::confirmFinalOrder Process Credit Transaction');
                return $this->processCreditTransaction($invoiceData);
        }
    }

    /**
     * Check to see if all key numbers are processed
     *
     * @return bool
     */
    public function areAllKeyNumbersProcessed()
    {
        $keyNumberCollection = $this->app->collection('keynumberlist');
        $keyNumberCollection->setParams(array('jobId' => $this->jobId));
        $keyNumbers = $keyNumberCollection->validateKeyNumbers($this->jobId);

        $keyNumberCollection->setParams(array('jobId' => $this->jobId,'tvcPaymentFailure' => '1'));
        $paymentFailedKeyNumbers = $keyNumberCollection->getAll();

        if(empty($keyNumbers) && empty($paymentFailedKeyNumbers)) {
            // If no key numbers are returned, the entire job has been prcessed
            return true;
        }

        return false;
    }

    /**
     * Check for valid key numbers, which are then added to the model variable $this->validKeyNumbers
     * for later use
     *
     * @return bool
     */
    public function retrieveValidatedKeyNumbers($override = false)
    {
        // Retrieve Validated Key Numbers
        $keyNumberCollection = $this->app->collection('keynumberlist');
        if($override === true){
            $keyNumberCollection->setParams(array('jobId' => $this->jobId, 'tvcPaymentFailure' => '1'));
        }
        else{
            $keyNumberCollection->setParams(array('jobId' => $this->jobId));
        }
        $keyNumbers = $keyNumberCollection->validateKeyNumbers($this->jobId, $override);
        $validKeyNumbers = array();
        foreach ($keyNumbers as $keyNumber) {
            if($keyNumber['valid']['valid'] == true) {
                $validKeyNumbers[] = $keyNumber;
            }
        }

        //if there are no key number
        if(empty($validKeyNumbers)){
            return false;
        }

        $keyNumberModel = $this->app->model('keyNumber');
        // Assign the cad numbers to them
        foreach ($validKeyNumbers as $keyNumber) {
            $tvcId = $keyNumber[0]['tvcId'];
            $this->validKeyNumbers[$tvcId] = $keyNumber[0];
            $this->validKeyNumbers[$tvcId]['requirements'] = $keyNumber['requirements'];
        }
    }

    public function processAccountTransaction($invoiceData)
    {
        // Start logging
        $loggingService = $this->app->service('Logger');
        $loggingService->setFilename('ConfirmFinalOrderForm');
        $loggingService->info('JobChecklist::processAccountTransaction');

        $keyNumberModel = $this->app->model('keyNumber');
        $jobPaymentService = $this->jobPaymentServiceModel;
        $westpacService = $this->app->service('westpacProcessing');

        $jobDetails = $jobPaymentService->getFullJob();

        $loggingService->info('JobChecklist::processAccountTransaction Job ID: ' . $jobDetails['jobId']);

        $amount = $jobPaymentService->calculateTotal(array_column($jobPaymentService->getItemsArray(),'item_amount_inc_gst_numeric'));

        $transactionData = array(
            'orderNumber'   => $invoiceData['id'],
            'amount'        => (float)$amount,
            'jobReferenceNo'=> $jobDetails['jobId'],
            'failed'=> false,
        );

        $loggingService->info('JobChecklist::processAccountTransaction Agency ID: ' . $jobDetails['agency']['agencyId']);
        $loggingService->info('JobChecklist::processAccountTransaction Transaction data: ' . json_encode($transactionData));

        $transaction = $westpacService->processAccTransaction($jobDetails['agency']['agencyId'], $transactionData);
        $loggingService->info('JobChecklist::processAccountTransaction Transaction result: ' . json_encode($transaction));

        // Assign the cad numbers to them
        $loggingService->info('JobChecklist::processAccountTransaction Assigning CAD Numbers for: ' . json_encode($this->validKeyNumbers));
        try {
            foreach ($this->validKeyNumbers as $tvcId => $keyNumber) {
                $keyNumberModel->assignCadNumber($tvcId);
            }
        } catch ( \Exception $e ) {
            $loggingService->error('JobChecklist::processAccountTransaction CAD Numbers assign exception: ' . $e->getMessage());
            throw $e;
        }
        $loggingService->info('JobChecklist::processAccountTransaction CAD Numbers assigned');

        $transactionData['transactionId'] = $transaction['id'];
        return $transactionData;
    }

    /**
     * Create a transaction entry for the overriden transaction
     * @param $invoiceData
     * @return array
     */
    public function processOverrideTransaction($invoiceData)
    {
        $keyNumberModel = $this->app->model('keyNumber');
        $jobPaymentService = $this->jobPaymentServiceModel;
        $westpacService = $this->app->service('westpacProcessing');

        $jobDetails = $jobPaymentService->getFullJob();

        $amount = $jobPaymentService->calculateTotal(array_column($jobPaymentService->getItemsArray(),'item_amount_inc_gst_numeric'));

        $transactionData = array(
            'orderNumber'   => $invoiceData['id'],
            'amount'        => (float)$amount,
            'referenceNo'=> $jobDetails['jobId'],
            'failed'=> false,
        );

        $transaction = $westpacService->processOverrideTransaction($jobDetails['agency']['agencyId'], $transactionData);

        // Assign the cad numbers to them
        foreach ($this->validKeyNumbers as $tvcId => $keyNumber) {
            $keyNumberModel->assignCadNumber($tvcId,true);
        }

        $transactionData['transactionId'] = $transaction['id'];
        return $transactionData;
    }

    public function processCreditTransaction($invoiceData)
    {
        // Start logging
        $loggingService = $this->app->service('Logger');
        $loggingService->setFilename('ConfirmFinalOrderForm');
        $loggingService->info('JobChecklist::processCreditTransaction');

        // Retrieve Validated Key Numbers
        $keyNumberModel = $this->app->model('keyNumber');
        $jobPaymentService = $this->jobPaymentServiceModel;
        /** @var \App\Services\WestpacProcessing $westpacService */
        $westpacService = $this->app->service('westpacProcessing');

        $jobDetails = $jobPaymentService->getFullJob();
        $loggingService->info('JobChecklist::processCreditTransaction Job ID: ' . $jobDetails['jobId']);

        $amount = $jobPaymentService->calculateTotal(array_column($jobPaymentService->getItemsArray(),'item_amount_inc_gst_numeric'));

        $transactionData = array(
            'orderNumber'   => $invoiceData['id'],
            'amount'        => (float)$amount,
            'jobReferenceNo'=> $jobDetails['jobId'],
            'failed'=> false,
            'agAccountType' => AGENCY_ACCOUNT_TYPE_COD
        );

        // Check if there is already a transaction for this invoice in the DB. If there is then
        // this is a broken invoice and we should complete the process using the existing transaction.
        $transactionObj = Transaction::where('order_no','=',$invoiceData['id'])->first();
        // If a transaction was found, DO NOT charge the client again.
        if(!empty($transactionObj)) {
            $loggingService->info('JobChecklist::processCreditTransaction Found old transaction: ' . $transactionObj->transaction_id);
            // Create the "transaction result" array so that the code can continue on.
            $transaction = ['failed' => false, 'id' => $transactionObj->transaction_id];
        }
        // If no transaction was found then continue with processing a new transaction
        else {
            $loggingService->info('JobChecklist::processCreditTransaction Agency ID: ' . $jobDetails['agency']['agencyId']);
            $loggingService->info('JobChecklist::processCreditTransaction Transaction data: ' . json_encode($transactionData));

            $transaction = $westpacService->processTransaction($jobDetails['agency']['agencyId'], $transactionData);
            $loggingService->info('JobChecklist::processCreditTransaction Transaction result: ' . json_encode($transaction));
        }

        if($transaction['failed'] !== true){
            $loggingService->info('JobChecklist::processCreditTransaction Assigning CAD Numbers for: ' . json_encode($this->validKeyNumbers));
            try {
                /**
                 * @var integer $tvcId
                 * @var KeyNumber $keyNumber
                 */
                foreach ($this->validKeyNumbers as $tvcId => $keyNumber) {
                    $keyNumberModel->assignCadNumber($tvcId);
                }
            } catch ( \Exception $e ) {
                $loggingService->error('JobChecklist::processCreditTransaction CAD Numbers assign exception: ' . $e->getMessage());
                throw $e;
            }
            $loggingService->info('JobChecklist::processCreditTransaction CAD Numbers assigned');
        } else {
            // Check if there was a westpac error
            if ( isset($transaction['westpacTransactionError']) ) {
                $transactionData['westpacTransactionError'] = $transaction['westpacTransactionError'];
            }
            else {
                // Mark key numbers as being rejected
                $loggingService->info('JobChecklist::processCreditTransaction Marking key numbers as being rejected: ' . json_encode($this->validKeyNumbers));
                try {
                    foreach ($this->validKeyNumbers as $tvcId => $keyNumber) {
                        $keyNumberModel->markAsFailedTransaction($tvcId);
                    }
                } catch ( \Exception $e ) {
                    $loggingService->error('JobChecklist::processCreditTransaction Key numbers rejection exception: ' . $e->getMessage());
                    throw $e;
                }
                $loggingService->info('JobChecklist::processCreditTransaction Key numbers marked as rejected');
            }

            $transactionData['failed'] = $transaction['failed'];

        }

        $transactionData['transactionId'] = $transaction['id'];

        return $transactionData;
    }

    public function overrideKeyNumbers($jobId)
    {
        $collection = $this->app->collection('keynumberlist');
        $this->jobId = $jobId;
        try {
            $collection->setParams(array('jobId' => $jobId,'tvcPaymentFailure' => '1'));
            $keyNumbers = $collection->getAll();

            //set validkeyNumbers in the expected format
            $this->validKeyNumbers = $this->getValidKeyNumbers(true);

            $keyNumberModel = $this->app->model('keyNumber');

            foreach($keyNumbers as $keyNumber) {
                $keyNumberModel->setIsOverride(1);
                $keyNumberModel->assignCadNumber($keyNumber['tvcId'],true);
            }

            return true;

        } catch (\Exception $e) {
            echo $e->getMessage();
            return false;
        }
    }

    private function assignCadNumbers()
    {
        $validKeyNumbers = $this->validKeyNumbers;
        $keyNumberModel = $this->app->model('keyNumber');
        // Assign the cad numbers to them
        foreach ($validKeyNumbers as $keyNumber) {
            $tvcId = $keyNumber[0]['tvcId'];
            $keyNumberModel->assignCadNumber($tvcId);
            $keyNumberModel->setTvcId($tvcId);
            $keyNumberModel->load();
            $keyNumber[0] = $keyNumberModel->getAsArray();
            $this->validKeyNumbers[] = $keyNumber;
        }
    }

    private function getFullJob()
    {
        $job = $this->app->model('job');
        $job->setJobId($this->jobId);
        $job->load();
        return $job->getFullJob();
    }

    public function getValidKeyNumbers($override = false)
    {
        if(empty($this->validKeyNumbers)){
            $this->retrieveValidatedKeyNumbers($override);
        }

        return $this->validKeyNumbers;
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function compileAdviceSlipData()
    {
        $adviceSlip = array();
        $time = new \DateTime('now');
        $capsule = $this->app->service('eloquent')->getCapsule();

        $adviceSlip['referenceNo'] = $this->jobId;
        $adviceSlip['jobId'] = $this->jobId;
        $adviceSlip['agencyName'] = $this->jobData['agency']['agencyName'];
        $adviceSlip['address'] = $this->jobData['agency']['address1'] . ' ' . $this->jobData['agency']['address2'] . '<br/>' . $this->jobData['agency']['city'] . ' ' . $this->jobData['agency']['postCode'] ;
        $adviceSlip['cadAssignedDate'] = $time->format('d-m-Y');
        $adviceSlip['advertiserName'] = $this->jobData['advertiser']['advertiserName'];

        $adviceSlipTemplateModel = new AdviceSlipTemplate();
        $data = [];
        $templates = $adviceSlipTemplateModel::all();
        foreach ($templates as $template) {
            $data[] = $template->getAsArray();
        }
        $template = array_shift($data);
        $adviceSlip['customContent'] = $template['adviceTemplate'];

        $tvcRequirementModel = new TvcRequirement();
        $adviceSlip['items'] = array();

        foreach ($this->validKeyNumbers as $tvcId => $keyNumber) {
            $requirementNotes = array();
            foreach ($keyNumber['requirements'] as $requirement) {
                if ($requirement['cadVisible'] == 1) {
                    $requirementNotes[] = $requirement['requirement']['agencyNotes'];
                }
            }
            // Reload the keynumber to get it's CAD number

            $keyNumberModel = $this->app->model('KeyNumber');
            $keyNumberModel->setTvcId($tvcId);
            $keyNumberModel->load();
            $keyNumber = $keyNumberModel->getAsArray();

            $requirementData = implode($requirementNotes, '<br/>** ');
            $adviceSlip['items'][] = array(
                'keyNumber' => $keyNumber['keyNumber'],
                'cadNumber' => $keyNumber['cadNumber'],
                'productDescription' => $keyNumber['description'],
                'length' => $keyNumber['length'],
                'rating' => $keyNumber['classification'],
                'requirementData' => $requirementData,
            );
        }
        if(empty($adviceSlip)) {
            throw new \Exception('Error in compiling advice slip');
        }
        return $adviceSlip;
    }

    public function compileFinalOrderFormData($jobId)
    {
        $data = array();

        $jobPaymentService = $this->app->service('JobPayment');

        // The following two lines are in case it's being accessed from somewhere else
        $this->jobId = $jobId;
        $this->jobData = $this->getFullJob();

        $collection = $this->app->collection('keynumberlist');
        $collection->setParams(array('jobId' => $jobId));

        $this->getFullJob();

        $keyNumbers = $collection->getAll();

        // charge code shit
        $chargeCodeModel = $this->app->model('chargeCode');
        $chargeCodes = array();
        $tvcFormat = '';
        // sum up late fees and get charge codes
        $lateFees = 0.00;
        $keyNumberList = array();
        $items = array();
        $totalPrice = 0.00;
        foreach($keyNumbers as $keyNumber) {
            $chargeCodeModel->setChargeCodeId($keyNumber['chargeCode']);
            $currentChargeCode = $chargeCodeModel->load();
            $showGst = $jobPaymentService->showGst($this->jobData['agency']);
            $split = $jobPaymentService->splitAmounts($currentChargeCode['billingRate'], $showGst);
            $itemAmountIncGST = bcdiv($split['item_amount_inc_gst'], 100, 4);
            $chargeCodes[] = $currentChargeCode;
            $item['keyNumber'] = $keyNumber['keyNumber'];
            $item['price'] = number_format($itemAmountIncGST, 2);
            $item['priceTotal'] = $item['price'];
            $item['description'] = $currentChargeCode['chargeCode'] . ' - ' . $currentChargeCode['description'];
            if(!empty($keyNumber['lateFee'])) {
                $lateFees = bcadd($lateFees,bcmul($itemAmountIncGST,bcdiv($currentChargeCode['lateFee'],'100',8),2),2);
            }
            $totalPrice = bcadd($totalPrice,$itemAmountIncGST,2);
            $items[] = $item;
        }

        // Collate processed manual adjustments as line items

        $collection = $this->app->collection('manualAdjustmentList');
        $collection->setParams(array('jobId' => $jobId,'processed' => 1));
        $manualAdjustments = $collection->getAll();

        foreach($manualAdjustments as $manualAdjustment) {
            $item['price'] = $manualAdjustment['maAmount'];
            $item['keyNumber'] = 'Manual adjustment';
            $item['description'] = $manualAdjustment['maDescription'];
            $item['priceTotal'] = $manualAdjustment['maAmount'];

            $totalPrice = bcadd($totalPrice,$manualAdjustment['maAmount'],2);
            $items[] = $item;
        }

        // Collate processed manual invoices as line items

        $invoiceModel = new Invoice();
        $invoiceCollection = $invoiceModel->where(array(
            'inv_job_id' => $jobId,
            'inv_invoice_type_id' => $this->app->config->manualInvoice,
        ));

        foreach($invoiceCollection->get() as $entity) {
            $invoice = $entity->toArray();
            $item['keyNumber'] = 'Manual Invoice';
            $item['price'] = bcadd($invoice['inv_amount_ex_gst'],$invoice['inv_gst'],2);
            $item['description'] = $invoice['inv_comment'];
            $item['priceTotal'] = bcadd($invoice['inv_amount_ex_gst'],$invoice['inv_gst'],2);

            $items[] = $item;
            $totalPrice = bcadd($totalPrice,$item['priceTotal'],2);
        }

        // Infomercials have no same day classification, hence different text
        $priorityProcessingText = ( $this->jobData['jobType']['jobTypeName'] == 'Infomercial' ) ? "PRIORITY PROCESSING FEE" : "PRIORITY PROCESSING FEE for same day classification";

        $data['jobId'] = $this->jobData['jobId'];
        $data['billTo'] = $this->jobData['agency']['agencyName'];
        $data['contactName'] = $this->jobData['agency']['primaryContactName'];
        $data['referenceNo'] = $this->jobData['jobId'];
        $data['phone'] = $this->jobData['agency']['phoneNumber'] . '<br/>' . $this->jobData['agency']['mobileNumber'] ;
        $data['purchaseOrder'] = $this->jobData['purchaseOrder'];
        $data['email'] = $this->jobData['agency']['primaryContactEmail'];
        $data['advertiserName'] = $this->jobData['advertiser']['advertiserName'];
        $data['lateFeePrice'] = number_format($lateFees, 2);
        $data['priorityProcessingText'] = $priorityProcessingText;
        $data['totalKeyNumberCount'] = count($keyNumbers);
        $data['totalPrice'] = number_format(bcadd($lateFees,$totalPrice,2), 2);
        $data['items'] = $items;
        $data['finalOrderComments'] = $this->jobData['jobFinalOrderComment'];

        return $data;
    }

    private function processDateTime($date)
    {
        $newDate = new \DateTime($date);
        return $newDate->format('d-m-Y');
    }

    public function getManualAdjustments()
    {
        return $this->manualAdjustments;
    }

    public function setTransactionId($transactionId)
    {
        $this->transactionId = $transactionId;
    }

    public function setJobPaymentModel(JobPayment $jobPayment)
    {
        $this->jobPaymentServiceModel = $jobPayment;
    }
}
