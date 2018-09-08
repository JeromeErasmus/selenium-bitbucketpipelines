<?php

namespace App\Services;

use App\Models\Job;
use Elf\Core\Module;
use App\Models\OrderForm as OrderFormModel;
use Elf\Exception\NotFoundException;
use Elf\Exception\MalformedException;
use App\Models\Country as Country;
use App\Models\State as State;
use Symfony\Component\Validator\Constraints\DateTime;

class JobPayment extends Module
{

    private $jobId;
    private $transactionId;
    private $finalOrderFormId;
    private $items = array();
    private $job;
    private $override = false;
    private $decimals = 2;
    // 13 is the max row we display in an invoice with the current height of each item row set in invoice-items.tpl.html
    // if there are more items it will got to another page but in a un-styled manner
    private $totalRows = 13;

    /**
     * Process final order form data and create invoice
     *
     * @param array $params
     * @return invoice
     * @throws MalformedException
     * @throws NotFoundException
     */
    public function process($params = array())
    {
        // Start logging
        $loggingService = $this->app->service('Logger');
        $loggingService->setFilename('ConfirmFinalOrderForm');
        $loggingService->info('JobPayment::process');

        $this->job = $this->getFullJob();

        $loggingService->info('JobPayment::process Job ID: ' . $this->job['jobId']);

        if(!array_key_exists('manualInvoiceType', $params)){
            $this->setFinalOrderFormItems($params);
            $loggingService->info('JobPayment::process Final order form items: ' . json_encode($this->items));
        }

        //get params for the invoice document
        $invoiceDocumentParams = $this->getInvoiceParams($params);
        $loggingService->info('JobPayment::process Invoice Document Params: ' . json_encode($invoiceDocumentParams));

        //if set invoice type to standard
        // A standard invoice is one triggered by the "Order Confirmation" button
        if(!array_key_exists('manualInvoiceType', $params)){
            // Add the job number so that any standard invoice created is always linked to a job.
            $invoiceDocumentParams['jobId'] = $this->job['jobId'];
            $invoiceDocumentParams['invoiceParams']['invoiceType'] = 1;
        }

        return $this->createInvoice($invoiceDocumentParams, $params["validKeyNumbers"]);
    }

    /**
     * Process a manual invoice
     *
     * @param array $params
     * @param bool $failedTransaction
     * @return bool
     * @throws MalformedException
     */
    public function processManualInvoice($params = array(), $failedTransaction = false)
    {
        $invoiceId = $params['invoiceId'];
        $this->job = $this->getFullJob();
        //at this point we know it exists
        if($params['invoiceAmount'] <= 0){
            throw new MalformedException("Manual Invoices are only processed for values greater than $0.00");
        }

        $showGst = $this->showGst($this->job['agency']);

        // We have tried to check negative-ness of the input, if they've gotten this far congratulations
        $manualInvoice = number_format(abs($params['invoiceAmount']),2); // we don't allow negative amounts for manual invoices
        $split = $this->splitAmounts($manualInvoice, $showGst);
        $amountExGst = $split['item_amount_ex_gst'];

        $this->items[] = array(
            'item_amount_ex_gst' => $amountExGst,
            'item_amount_inc_gst' => $manualInvoice,
            'item_amount_inc_gst_numeric' => bcmul($manualInvoice,100,0),
            'late_fee' => $this->formatAmount(0,100),
            'gst' => $split['gst'],
            'gst_numeric' => $split['gst'],
            'item_description' => $params['invoiceComment'],
        );

        $params['amountExGst'] = $amountExGst;
        $params['amountIncGst'] = $manualInvoice;
        $params['gst'] = $split['gst'];
        $params['invoiceType'] = $this->app->config->manualInvoice; // 2 is the invoice_type_id for manual invoices

        $invoiceService = $this->app->service('invoice');
        $invoiceService->invoice($params);

        if($failedTransaction !== true){
            $params = $this->setInvoiceDocumentData();
            $invoiceDocument = $invoiceService->createInvoiceDocument($invoiceId, $params);
            return $invoiceDocument;
        }
        return false;
    }

    /**
     * prepare params for creation of invoice (create document and insert record in invoice table)
     *
     * @param $invoiceDocumentParams
     * @param $keyNumbersArray
     * @return mixed
     */
    public function createInvoice($invoiceDocumentParams, $keyNumbersArray)
    {
        /** @var Invoice $invoiceService */
        $invoiceService = $this->app->service('invoice');

        return $invoiceService->create($invoiceDocumentParams, $keyNumbersArray);
    }

    /**
     * Given an agency array, returns a boolean that indicates whether their invoice should show gst or not
     * @param $agencyData
     * @return bool
     */
    public function showGst($agencyData)
    {
        // If it's australian, show agency
        if ($this->isAustralian($agencyData['country']) == true) {
            return true;
        }
        // If the overseasGstStatusApproved is equal to 1, that means you don't show gst (it's more an exemption than an approval)
        return $agencyData['overseasGSTStatusApproved'] != 1;
    }

    /**
     * Given a country id, return a boolean indicating whether the country in question is Australia
     * @param $countryId
     * @return bool (true bool)
     */
    public function isAustralian($countryId)
    {
        $capsule = $this->app->service('eloquent')->getCapsule();
        $australia = Country::where('cty_name', '=', 'Australia')->get()->toArray();
        return $australia[0]['cty_id'] == $countryId ? true : false;
    }

    public function setInvoiceDocumentData()
    {
        //get params for the invoice document
        $invoiceDocumentParams = $this->getInvoiceParams();
		$invoiceDocumentParams['grand_total'] = preg_replace( "/,/", "", $invoiceDocumentParams['grand_total']);
		
        $params = array();
        $date = new \DateTime();

        // Due date is the end of the next month
        $dueDate = new \DateTime();
        $dueDate = $dueDate->add(new \DateInterval('P1M'));


        $invoiceHeader = 'INVOICE TOTAL';

        $capsule = $this->app->service('eloquent')->getCapsule();
        $australia = Country::where('cty_name', '=', 'Australia')->get()->toArray();
        if($australia[0]['cty_id'] == $this->job['agency']['country']) {
            $invoiceHeader = 'INVOICE (inc GST)';
        }

        $params['documentParams'] = array(
            'jobId' => $this->jobId,
            'invoice_tax_type' => $invoiceDocumentParams['invoice_tax_type'],
            'customer_name'=> $this->job['agency']['agencyName'],
            'customer_address_1'=> empty($this->job['agency']['address1']) ? $this->job['agency']['address2'] : $this->job['agency']['address1'],
            'customer_address_2'=> $this->job['agency']['address1'] == $this->job['agency']['address2'] ? '' : $this->job['agency']['address2'],
            'customer_city' => $this->job['agency']['city'],
            'customer_postcode' => $this->job['agency']['postCode'],
            'customer_state' => $this->job['agency']['state'],
            'customer_country' => $this->job['agency']['country'],
            'paid' => $invoiceDocumentParams['paid'],
            'purchase_order' => empty($this->job['purchaseOrder']) ? '' : $this->job['purchaseOrder'],
            'customer_code' => $this->job['agency']['agencyCode'],
            'invoice_date' => $date->format('d/m/Y'),
            'due_date_header' => $invoiceDocumentParams['due_date_header'],
            'due_date' => (isset($invoiceDocumentParams['due_date']) ? $invoiceDocumentParams['due_date'] : $dueDate->format('t/m/Y')),
            'account_type' => $invoiceDocumentParams['account_type'],
            'account_terms' => $invoiceDocumentParams['account_terms'],
            'gst_total' =>  number_format($invoiceDocumentParams['gst_total'],2),
            'grand_total' => number_format($invoiceDocumentParams['grand_total'],2),
            'invoice_header' => $invoiceHeader,
            'total_column_name' => $invoiceDocumentParams['total_column_name'],
            'eft_banking_details_header' => $invoiceDocumentParams['eft_banking_details_header'],
            'financial_institution_header' => $invoiceDocumentParams['financial_institution_header'],
            'financial_institution_details' => $invoiceDocumentParams['financial_institution_details'],
            'eft_banking_details' => $invoiceDocumentParams['eft_banking_details'],
            'acc_name_details_header' => $invoiceDocumentParams['acc_name_details_header'],
            'acc_name_details' => $invoiceDocumentParams['acc_name_details'],
            'payment_note' => $invoiceDocumentParams['payment_note'],
        );

        $params['documentParams']['customer_address'] = $this->getFormattedCustomerAddress($this->job['agency']); // get the customer address block for the {%customer_address%} replacement code in header.tpl.html

        if(!empty($this->items)){
            $this->padItems();
            $params['documentParams']['items'] = $this->items;
        }

        return $params;
    }

    /**
     * getFormattedCustomerAddress
     * 
     * handle data migration issues with agency address block for invoices and format in a HTML formatted block
     *
     * @param [array] $agency
     * @return string
     */
    private function getFormattedCustomerAddress($agency)
    {

        $address = "";

        $usedAddress2 = false; // flag to see if Address 2 has been used in place of Address 1

        if(!is_array($agency) || empty($agency)) {
        
            return $address;
        
        }

        $capsule = $this->app->service('eloquent')->getCapsule();
        $country = Country::getCountryNameFromCountryId($agency['country']);

        $state = State::getStateNameFromStateId($agency['state']);

        if(!empty($agency['agencyName'])) {

            $address .= $agency['agencyName'] . "<br>";

        }

        if(empty( $agency['address1'])) { // sometimes address 1 is empty but address 2 is filled in migrated data

            $address .= $agency['address2'];

            $usedAddress2 = true;

        } else {

            $address .=  $agency['address1'];

        }

        if( $agency['address1'] !=  $agency['address2'] &&
           false === $usedAddress2) { // only use address 2 if it has not already been used and if it isn't the same as address 1

            $address .= ", {$agency['address2']}"; 

        }

        $address .= "<br>";

        if(!empty($agency['city'])) {

            $address .= $agency['city'];
        
        }

        if(!empty($state)) {

            $address .= ", $state";

        }

        if(!empty($agency['postCode'])) {
            $address .= ", {$agency['postCode']}";
        }

        if(!empty($country)) {
        
           $address .= "<br>$country";

        }

        $address .= "<br><br>";

        return $address;

    }

    /**
     * Updates an invoice with the transaction id
     *
     * @param $invoiceId
     * @param bool $failedTransaction
     * @return bool
     */
    public function updateInvoice($invoiceId, $failedTransaction = false)
    {
        $invoiceService = $this->app->service('invoice');

        $invoiceService->invoice(array('invoiceId' => $invoiceId,
            'transactionId' => $this->transactionId,
            'jobId' => $this->jobId
        ));

        if($failedTransaction !== true){
            $params = $this->setInvoiceDocumentData();
            $invoiceDocument = $invoiceService->createInvoiceDocument($invoiceId, $params);
            return $invoiceDocument;
        }
        return false;

    }

    /**
     * prepare items for the invoice, merge in one array keyNumbers and manual adjustments
     *
     * $this->items(
     *     0 => array(
     *              'item_amount_ex_gst' => <amount with excluding GST>
    'item_amount_inc_gst' => <amount including GST>,
    'gst' => <GST>,
    'item_description' => <item description>,
    'item_qty' => <qty>
     *          ),
     *      1 => array(
     *          )
     * @param type $data array is set when not all TVC have a CAD number assigned (partial final order form)
     *
     * @throws NotFoundException
     */
    public function setFinalOrderFormItems($data = array())
    {
        // we are processing a final order form, all data in the blob
        if(isset($this->finalOrderFormId)){
            //get validated data from the orderForm table
            $orderForm = OrderFormModel::findOrFail($this->finalOrderFormId)->toArray();
            $inputData = json_decode($orderForm['input_data'], true);

        }else{
            // if not all cad number are assigned we use posted data and job top prepare the input data array
            $inputData = array(
                'keyNumbers' => !empty($data['validKeyNumbers']) ? $data['validKeyNumbers'] : array(),
                'manualAdjustments' => !empty($data['manualAdjustments']) ? $data['manualAdjustments'] : array(),
            );
        }

        $showGst = $this->showGst($this->job['agency']);

        $params = array(
            'authorisedCharity' => $this->job['advertiser']['authorisedCharity'],
            'showGst' => $showGst,
        );

        $keyNumbers = !empty($inputData['keyNumbers']) ? $inputData['keyNumbers'] : array();
        $manualAdjustments = !empty($inputData['manualAdjustments']) ? $inputData['manualAdjustments'] : array();

        // if we don't have any valid key number we should throw and exception and stop processing
        if(!empty($keyNumbers)){
            // this is for keyNumbers
            foreach($keyNumbers as $tvcArray){

                $params['lateFee'] = $tvcArray['lateFee'];
                $amounts = $this->getItemAmountsFromChargeCode($tvcArray['chargeCode'], $params);

                $this->items[] = array(
                    'tvcId' => $tvcArray['tvcId'],
                    'item_amount_ex_gst' => $amounts['item_amount_ex_gst'] ,
                    'item_amount_inc_gst' => $this->formatAmount($amounts['item_amount_inc_gst'], 100),
                    'item_amount_inc_gst_numeric' => $amounts['item_amount_inc_gst'],
                    'gst' => $amounts['gst'],
                    'gst_numeric' => $amounts['gst'],
                    'item_description' => $tvcArray['jobId'].' '.$tvcArray['keyNumber'],
                );
                // Late Fee line item
                if (!empty($amounts['lateFeeDetails'])) {
                    $lateFeeDetails = $amounts['lateFeeDetails'];
                    $this->items[] = array(
                        'tvcId' => $tvcArray['tvcId'],
                        'item_amount_ex_gst' => $lateFeeDetails['late_fee_ex_gst'] ,
                        'item_amount_inc_gst' => $this->formatAmount($lateFeeDetails['late_fee_inc_gst'], 100),
                        'item_amount_inc_gst_numeric' => $lateFeeDetails['late_fee_inc_gst'],
                        'gst' => $lateFeeDetails['late_fee_gst'],
                        'gst_numeric' => $lateFeeDetails['late_fee_gst'],
                        'item_description' => 'Priority Processing Fee - '.$tvcArray['keyNumber'],
                    );
                }
            }

            // manual adjustments
            foreach($manualAdjustments as $manualAdjustmentType){
                foreach($manualAdjustmentType as $manualAdjustment){
                    $manualAdjustmentAmount = $manualAdjustment['maAmount'];
 
                    $split = $this->splitAmounts($manualAdjustmentAmount, $showGst);
					
                    $amountExGst = $split['item_amount_ex_gst'];
                    $this->items[] = array(
                        'item_amount_ex_gst' => $amountExGst,
                        'item_amount_inc_gst' => number_format($manualAdjustment['maAmount'],2),
                        'item_amount_inc_gst_numeric' => $manualAdjustment['maAmount']*100,
                        'late_fee' => $this->formatAmount(0,100),
                        'gst' => $split['gst'],
                        'gst_numeric' => $split['gst'],
                        'item_description' => $manualAdjustment['maDescription'],
                    );
                }
            }
        }else{
            throw new NotFoundException("There are no key numbers to be processed");
        }
    }



    /**
     * Given an amount including the gst it calculates the gst and the price excluding gst
     * if show gst is true, calculate the gst, otherwise, just set it 0
     * @param string $amount
     * @param boolean $showGst
     * @return array(
     *      'gst' => <gst amount>
     *      'item_amount_ex_gst' => <item price excluding gst>
     *  )
     */
    public function splitAmounts($amount, $showGst = true)
    {
		$amount = bcmul($amount, 100, 4);

        $gst = $this->getGst($amount);
        $amount = bcdiv($amount,100,4);
        $gst = bcdiv($gst,100,4);
        $price = bcsub($amount,$gst,4);

        // Clear GST
        if($showGst != true){
            $gst = 0;
        }

        $itemAmountIncGst = (round($gst,2) + round($price,2)) * 100;
        $gst = number_format(round($gst,2),2);
        $itemAmountExGst = number_format(round($price,2),2);

        return array(
            'gst' => $gst,
            'item_amount_ex_gst' => $itemAmountExGst,
            'item_amount_inc_gst' => $itemAmountIncGst
        );
    }

    /**
     * calculate GST
     * @param type $amount
     * @return int
     */
    private function getGst($amount)
    {
		$gst = bcdiv($amount, 11, 4);
        return $gst;
    }

    /**
     * get amount including gst and excluding gst from a charge code
     *
     * @param $chargeCodeId
     * @param array $params
     * @return array
     */
    private function getItemAmountsFromChargeCode($chargeCodeId, $params = array())
    {
        $chargeCode = $this->getChargeCodeById($chargeCodeId);
        $appliedLateFee = 0;
        $amountIncGst = $this->getItemAmount($chargeCode, $params);
        //amount of ex gst is calculated without late fee
        $split = $this->splitAmounts($chargeCode['billingRate'], $params['showGst']);
        $amountExGst = $split['item_amount_ex_gst'];
        $gst = $split['gst'];
        $lateFeeDetails = [];

		
        if($params['lateFee'] == 1){
            $lateFee = $chargeCode['lateFee'];
            $appliedLateFee = ((int)$amountIncGst * $lateFee) / 10000;
            $lateFeeSplit = $this->splitAmounts($appliedLateFee,$params['showGst']);

            $lateFeeDetails = [
                'late_fee_inc_gst' => $lateFeeSplit['item_amount_inc_gst'],
                'late_fee_ex_gst' => $lateFeeSplit['item_amount_ex_gst'],
                'late_fee_gst' => $lateFeeSplit['gst'],
            ];
        }

        return array(
            'item_amount_inc_gst' => $split['item_amount_inc_gst'],
            'item_amount_ex_gst' => $amountExGst,
            'gst' => $gst,
            'lateFee' => $appliedLateFee,
            'lateFeeDetails' => $lateFeeDetails,
        );

    }

    /**
     * @param $chargeCode
     * @param array $params
     * array(
     *  "late_fee" => <value>,
     *  "discount" => <value>
     * )
     * @return int
     */
    private function getItemAmount($chargeCode, $params = array())
    {

        $billingRate = (int)($chargeCode['billingRate']*100);


        return $billingRate;
    }

    /**
     * get the full job
     * @return type
     */
    public function getFullJob($jobId = null)
    {
        if(!empty($jobId)){
            $this->jobId = $jobId;
        }

        /** @var Job $jobObj */
        $jobObj = $this->app->model('job');
        $jobObj->setJobId($this->jobId);
        $jobObj->load();
        return $jobObj->getFullJob();
    }


    /**
     * get chargeCode by chargeCode id
     * @param type $chargeCodeId
     * @return type
     */
    public function getChargeCodeById($chargeCodeId)
    {
        $chargeCodeModel = $this->app->model('chargeCode');

        $chargeCodeModel->setChargeCodeId($chargeCodeId);
        return $chargeCodeModel->load();
    }

    /**
     * get params for invoice such as labels and the account type
     *
     * @param array $data
     * @return array
     */
    private function getInvoiceParams($data = array())
    {
        $agency = $this->job['agency'];

        $gstTotal = $this->calculateTotal(array_column($this->items,'gst_numeric'));
        $grandTotal = $this->calculateTotal(array_column($this->items,'item_amount_inc_gst_numeric'));
        
        $params = array(
            'paid' => '',
            'invoice_tax_type' => 'TAX INVOICE',
            'account_terms' => 'COD',
            'account_type' => $agency['accountType'],
            'gst_total' => $gstTotal,
            'grand_total' => $this->formatAmount($grandTotal, 100),
            'total_column_name' => 'INCLUSIVE OF GST',
            'due_date_header' => 'DUE DATE',
            'eft_banking_details_header' => 'EFT Banking Details',
            'eft_banking_details' => 'BSB 032-097 A/C 360-470',
            'acc_name_details_header' => 'A/C Name:',
            'acc_name_details' => 'Free TV Australia',
            'financial_institution_header' => 'Financial institution:',
            'financial_institution_details' => 'Westpac Banking Corporation',
            'payment_note' => 't/a Commercials Advice'
        );
        $credit = false;
        //manual invoice params
        // If this is a Manual Invoice then is it an Additional Charge or a Credit.
        if(array_key_exists('manualInvoice', $data)){
            $credit = $data['manualInvoice']['manualInvoiceType'] == 'additionalCharge' ? false : true;
        }

        // overseas agencies that are GST Approved
        if($agency['overseasGSTStatusApproved'] == 1){
            $params['invoice_tax_type'] = 'INVOICE';
            $params['gst_total'] = 0.00;
            $params['total_column_name'] = 'TOTAL';
        }
        // credit invoice
        if($credit){
            $params['paid'] = '';
            $params['invoice_tax_type'] = 'ADJUSTMENT NOTICE';
        }
        // COD agencies
        if($agency['accountType'] == AGENCY_ACCOUNT_TYPE_COD && !$this->isOverride()){
            $params['paid'] = 'PAID';
            $params['account_terms'] = $agency['accountType'];
            $params['due_date_header'] = '';
            $params['due_date'] = '';
            $params['eft_banking_details_header'] = '';
            $params['eft_banking_details'] = '';
            $params['acc_name_details_header'] = '';
            $params['acc_name_details'] = '';
            $params['financial_institution_header'] = '';
            $params['financial_institution_details'] = '';
            $params['payment_note'] = '';
        }

        return $params;
    }

    /**
     * as we multiply values by 100 to ensure calculations are correct,
     * before displaying we need re-format figures
     *
     * @param $amount
     * @param $operator
     * @return string
     */
    private function formatAmount($amount, $operator)
    {
        $result = 0;
        if($amount > 0){
            $result = bcdiv($amount,$operator, $this->decimals);
        }
        return number_format($result, 2);
    }

    /**
     * calculate the sum of array items using bcadd function
     *
     * @param type $array
     * @return string
     */
    public function calculateTotal($array)
    {

        $sum = 0;
        foreach($array as $value){
            $sum = bcadd($sum, $value, 2);
        }
        return $sum;
    }

    /**
     * Add empty elements to $this->items until it reaches totalRows
     * We need this hack to have the invoice table displaying correctly
     */
    private function padItems()
    {
        $itemCount = count($this->items);

        if($itemCount < $this->totalRows){
            for($i=0; $i <= ($this->totalRows - $itemCount); $i++){
                $this->items[] = array(
                    'item_amount_ex_gst' => '',
                    'item_amount_inc_gst' => '',
                    'gst' => '',
                    'item_description' => '',
                );
            }
        }
    }

    /**
     * set job id
     * @param type $jobId
     */
    public function setJobId($jobId)
    {
        $this->jobId = $jobId;
    }

    /**
     * set final order form
     * @param type $finalOrderFormId
     */
    public function setFinalOrderFormId($finalOrderFormId)
    {
        $this->finalOrderFormId = $finalOrderFormId;
    }

    /**
     * set transaction id
     * @param type $transactionId
     */
    public function setTransactionId($transactionId)
    {
        $this->transactionId = $transactionId;
    }

    public function getItemsArray(){
        return $this->items;
    }

    /**
     * @return bool
     */
    public function isOverride()
    {
        return $this->override;
    }

    /**
     * @param bool $override
     */
    public function setOverride($override)
    {
        $this->override = $override;
    }

    /**
     * Update TVC total charge, charge ex GST and GST
     * @param $items
     */
    public function updateTVCAmounts($items)
    {
        // Add priority processing fee values
        $keyNumbers = $this->addPPFValues($items);

        foreach ( $keyNumbers as $aKeyNumber ) {
            // Update only if there is a tvcId
            if ( isset($aKeyNumber['tvcId'] ) ) {
                $keyNumberModel = $this->app->model('KeyNumber');
                $keyNumberModel->setTvcId($aKeyNumber['tvcId']);
                $keyNumberModel->load();

                // Update amount columns
                $keyNumberModel->setTvcTotalCharge(floatval($aKeyNumber['item_amount_inc_gst']));
                $keyNumberModel->setTvcChargeExGST(floatval($aKeyNumber['item_amount_ex_gst']));
                $keyNumberModel->setTvcChargeGST(floatval($aKeyNumber['gst']));

                $keyNumberModel->save();
            }
        }
    }

    /**
     * Add priority processing fee values
     * @param $items
     * @return array
     */
    private function addPPFValues($items)
    {
        $keyNumbers = [];

        // If key number has PPF there will be two elements with same TvcID
        foreach ( $items as $anItem ) {
            if ( !isset($keyNumbers[$anItem['tvcId']]) ) {
                $keyNumbers[$anItem['tvcId']] = [
                    'tvcId' => $anItem['tvcId'],
                    'item_amount_inc_gst' => $anItem['item_amount_inc_gst'],
                    'item_amount_ex_gst' => $anItem['item_amount_ex_gst'],
                    'gst' => $anItem['gst']
                ];
            }
            else {
                // TvcID already exists so add PFF values
                $keyNumbers[$anItem['tvcId']]['item_amount_inc_gst'] += $anItem['item_amount_inc_gst'];
                $keyNumbers[$anItem['tvcId']]['item_amount_ex_gst'] += $anItem['item_amount_ex_gst'];
                $keyNumbers[$anItem['tvcId']]['gst'] += $anItem['gst'];
            }
        }

        return $keyNumbers;
    }


}
