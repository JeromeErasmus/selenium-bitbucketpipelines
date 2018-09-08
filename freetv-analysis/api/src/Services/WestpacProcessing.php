<?php
/**
 * Created by PhpStorm.
 * User: mchan
 * Date: 28/06/16
 * Time: 3:01 PM
 */

namespace App\Services;

use Elf\Event\AbstractEvent;
use Elf\Exception\MalformedException;
use Elf\Exception\NotFoundException;
use App\Models\Transaction as Transaction;

class WestpacProcessing extends AbstractEvent
{

    public function default_event()
    {
        throw new \Exception("No default method, please check usage");
    }

    /**
     * Process the transaction where $inputData takes a form similar to
     *
    {
    "transactionId": "123" -> set only when we are editing an existing transaction
    "type": "capture",
    "PAN":"12341234123",
    "CVN":"123",
    "expYear":"2016",
    "expMonth":"03",
    "cardName":"123test",
    "amount":"12312312312312312321123123",
    "orderNumber":"123qweqwe",
    "currency":"AUD",
    "ECI":"IVR",
    "originalOrderNumber":"123qweqwe",
    "originalReferenceNumber":"123qweqwe",
    "authId":"qweqwe",
    "custRefNo":"123",
    "preRegCode":"321123123",
    "xid":"123",
    "cavv":"1234123412341234123412341234",
    "ipAddress":"123.123.123.123"
    }
     *
     *
     * @param $agencyId
     * @param $inputData
     * @param int $clientId
     * @param bool $reCheck if set to true update an exisiting transaction
     * @return \App\Models\type|array|bool|void
     * @throws NotFoundException
     */
    public function processTransaction($agencyId, $inputData, $clientId = 1, $reCheck = false)
    {
        // Start logging
        $loggingService = $this->app->service('Logger');
        $loggingService->setFilename('ConfirmFinalOrderForm');
        $loggingService->info('WestpacProcessing::processTransaction');

        $exisitingTransactionModel = null;
        $exitingTransactionData = array();
        //if transactionId is set we are
        if($reCheck === true){
            $exisitingTransactionModel = Transaction::findOrFail($inputData['transactionId']);
            $exitingTransactionData = $exisitingTransactionModel->getAsArray();
            $loggingService->info('WestpacProcessing::processTransaction Re-checking. Existing transaction data: ' . json_encode($exitingTransactionData));
        }

        $qvalent = $this->app->service( 'Qvalent_CardsAPI' );
        if( !$qvalent->isInitialised() ){
            $qvalent->initialise( $this->app->config->qvalentParams );
        }

        //as we should always be associating an agency to a transaction we can assume we have the agencyId
        $agencyModel = $this->app->model('agency');
        $agencyDetails = $agencyModel->getById($agencyId);
        //load only the primary token
        $primaryToken = $this->primaryToken($agencyId);

        $inputData['preRegCode'] = $primaryToken['westpacToken'];
        $inputData['cardName'] = $agencyDetails['agencyName'];

        if( !$this->checkTransactionValidity( $inputData ) ){
            return $this->checkTransactionValidity( $inputData );
        }

        try{
            $outputData = $this->formatInputData( $inputData );
            $loggingService->info('WestpacProcessing::processTransaction Processing transaction. Input data: ' . json_encode($outputData));
            $transaction = $qvalent->processCreditCard( $outputData );
            $loggingService->info('WestpacProcessing::processTransaction Transaction proccesed. Transaction data: ' . json_encode($transaction));

            // Add agency account type
            if ( isset($inputData['agAccountType']) && AGENCY_ACCOUNT_TYPE_COD == $inputData['agAccountType'] ) {
                $transaction['response.agAccountType'] = AGENCY_ACCOUNT_TYPE_COD;
            }

            //take the transaction end and place it in the transactions table
            $capsule = $this->app->service('eloquent')->getCapsule();
            // if it's a new transaction create new model, if it's an existing one use it
            $transactionModel = $reCheck === true ? $exisitingTransactionModel : new Transaction();
            // set transaction properties with data coming from credit card
            $transactionModel->setFromArray($transaction);

            if($transactionModel->validate()) {

                $transactionModel->save();
                $data = $transactionModel->getAsArray();
                $data['failed'] = false;

                $url = "/transaction/clientId/".$clientId."/id/" . $data['id'];
                $this->set('locationUrl', $url);

                if ($transaction['response.summaryCode'] == $this->app->config->westpacTransactionError){
                    //if we are checking an existing transaction we need to check how many time the transaction has been checked,
                    if($reCheck === true){
                        // if it's the first time we check it mark it as checked by updating the transactionModel
                        if($exitingTransactionData['response.unknownRecheckCount'] < $this->app->config->unknownRecheckCountLimit){
                            $data['response.unknownRecheckCount'] = ++$data['response.unknownRecheckCount'];
                            $transactionModel->setFromArray($data);
                            $transactionModel->save();
                        }
                    }

                    $loggingService->info('WestpacProcessing::processTransaction Westpac transaction error. response.summaryCode: ' . $transaction['response.summaryCode']);

                    // Counts as a fail since we can't know if the transaction went through
                    $data['failed'] = true;
                    $data['westpacTransactionError'] = true;
                    $this->set('status_code', 402);
                }else if ($transaction['response.summaryCode'] == $this->app->config->westpacTransactionDeclined ||
                    $transaction['response.summaryCode'] == $this->app->config->westpacTransactionRejected){
                    $loggingService->info('WestpacProcessing::processTransaction Westpac transaction not approved. response.summaryCode: ' . $transaction['response.summaryCode']);

                    //at the moment this is used to see if we should generate a document
                    $data['failed'] = true;
                    $this->set('status_code', 402);
                } else if ($transaction['response.summaryCode'] == $this->app->config->westpacTransactionApproved) {
                    $loggingService->info('WestpacProcessing::processTransaction Westpac transaction approved');
                    $this->set('status_code', 201);
                }

                return $data;
            }

            $loggingService->error('WestpacProcessing::processTransaction Transaction model was invalid');

            //this should never be reached as data is never processed by a user
            $this->set('status_code', 400);

            return ['failed' => true ];

        }
        catch( \Exception $e ){
            $loggingService->error('WestpacProcessing::processTransaction Exception: ' . $e->getMessage());
            echo "Failed: " . $e->getMessage(  );
        }

        return ['failed' => true ];
    }

    /**
     * Load primary token
     * @param type $agencyId
     * @return type
     */
    private function primaryToken($agencyId)
    {
        $tokenModel = $this->app->model('westpacToken');
        $tokenModel->setAgencyId($agencyId);
        $tokenModel->getAgencyPrimaryToken();
        return $tokenModel->getAsArray();
    }

    public function processAccTransaction($agencyId, $inputData, $clientId = 1)
    {
        $tokenModel = $this->app->model('westpacToken');

        $tokenModel->setAgencyId($agencyId);

        if( !$this->checkTransactionValidity( $inputData ) ){
            return $this->checkTransactionValidity( $inputData );
        }

        try{
            //take the transaction end and place it in the transactions table
            $capsule = $this->app->service('eloquent')->getCapsule();
            $transactionModel = new Transaction();
            //set the transaction for something that is obvious its an account transaction
            $transaction = array(
                'response.summaryCode'  => '0',  //Always mark the transaction as approved
                'response.responseCode' => (isset($inputData['manualInvoiceType'])
                    && ($inputData['manualInvoiceType'] == 'credit' ) ) ? 'ACCR' :'ACC', //Accounts code, set for FTV
                'response.text'         => (isset($inputData['manualInvoiceType'])
                    && ($inputData['manualInvoiceType'] == 'credit' ) ) ? 'Credit transaction' :'Processing accounts transaction',
                'response.referenceNo'  => $inputData['referenceNo'],
                'response.agAccountType' => AGENCY_ACCOUNT_TYPE_ACC
            );
            $transactionModel->setFromArray($transaction);

            if($transactionModel->validate()) {
                $transactionModel->save();
                $data = $transactionModel->getAsArray();

                $url = "/transaction/clientId/".$clientId."/id/" . $data['id'];
                $this->set('locationUrl', $url);
                $this->set('status_code', 200);

                return $data;
            }

            //this should never be reached as data is never processed by a user
            $this->set('status_code', 400);

            return $transactionModel->errors;

        }
        catch( \Exception $e ){
            echo "Failed: " . $e->getMessage(  );
        }
        return;

    }

    public function processOverrideTransaction($agencyId, $inputData, $clientId = 1)
    {
        $tokenModel = $this->app->model('westpacToken');

        $tokenModel->setAgencyId($agencyId);

        if( !$this->checkTransactionValidity( $inputData ) ){
            return $this->checkTransactionValidity( $inputData );
        }

        try{
            //take the transaction end and place it in the transactions table
            $capsule = $this->app->service('eloquent')->getCapsule();
            $transactionModel = new Transaction();
            //set the transaction for something that is obvious its an account transaction
            $transaction = array(
                'response.summaryCode'  => '0',  //Always mark the transaction as approved
                'response.responseCode' => 'M.O.', //override code
                'response.text'         => 'Manual Override',
                'response.referenceNo'  => $inputData['referenceNo'],
            );
            $transactionModel->setFromArray($transaction);

            if($transactionModel->validate()) {
                $transactionModel->save();
                $data = $transactionModel->getAsArray();

                $url = "/transaction/clientId/".$clientId."/id/" . $data['id'];
                $this->set('locationUrl', $url);
                $this->set('status_code', 200);

                return $data;
            }

            //this should never be reached as data is never processed by a user
            $this->set('status_code', 400);

            return $transactionModel->errors;

        }
        catch( \Exception $e ){
            echo "Failed: " . $e->getMessage(  );
        }
        return;

    }

    /**
     * Checks if all the input data, is valid and as expected with reference to transaction type
     *
     * @param $inputData
     * @return bool
     * @throws NotFoundException
     */
    private function checkTransactionValidity($inputData, $token = true)
    {
        if (!isset($inputData['type'])) {
            $inputData['type'] = '';
        }

        if( strtolower( $inputData['type'] ) != 'capturewithoutauth' && isset( $inputData['authID'] ) ) {
            $this->set( 'status_code', 400 );
            throw new NotFoundException(['displayMessage' => 'Error: Invalid transaction authorisation'] );
        }

        if( strtolower( $inputData['type'] ) == 'capture' ){
            if( (!isset( $inputData['PAN']) ||
                !isset( $inputData['expYear'] ) ||
                !isset( $inputData['expMonth'] ) && $token === false)
            ) {
                $this->set( 'status_code', 400 );
                throw new NotFoundException(['displayMessage' => 'Error: This transaction type requires valid Card details (CC)'] );
            }
            else if( $token === true && !isset($inputData['preRegCode']) ){
                $this->set( 'status_code', 400 );
                throw new NotFoundException(['displayMessage' => 'Error: This transaction type requires valid Card details (TK)'] );
            }

            if( !isset($inputData['amount'] ) ){
                $this->set( 'status_code', 400 );
                throw new NotFoundException(['displayMessage' => 'Error: There is no amount specified for this transaction'] );
            }

            if( !isset($inputData['orderNumber']) ){
                $this->set( 'status_code', 400 );
                throw new NotFoundException(['displayMessage' => 'Error: Your query requires an Order Number'] );
            }
        }
        elseif( strtolower( $inputData['type'] ) == 'preauth' ) {
            if( (!isset( $inputData['PAN']) ||
                !isset( $inputData['expYear'] ) ||
                !isset( $inputData['expMonth'] ) && $token === false)
            ) {
                $this->set( 'status_code', 400 );
                throw new NotFoundException(['displayMessage' => 'Error: This transaction type requires valid Card details (CC)'] );
            }
            else if( $token === true && $inputData['preRegCode'] ){
                $this->set( 'status_code', 400 );
                throw new NotFoundException(['displayMessage' => 'Error: This transaction type requires valid Card details (TK)'] );
            }
            if ( !isset($inputData['orderNumber'] )
            ) {
                $this->set('status_code', 400);
                throw new NotFoundException(['displayMessage' => 'Error: Your query requires an Order Number'] );
            }

            if( !isset($inputData['amount'] ) ){
                $this->set( 'status_code', 400 );
                throw new NotFoundException(['displayMessage' => 'Error: There is no amount specified for this transaction'] );
            }

        }
        elseif(strtolower( $inputData['type'] ) == 'query'){
            if( !isset( $inputData['orderNumber'] ) ){
                $this->set( 'status_code', 400 );
                throw new NotFoundException(['displayMessage' => 'Error: Your query requires an Order Number'] );
            }
        }
        elseif(strtolower( $inputData['type'] ) == 'refund'){
            if( !isset( $inputData['orderNumber'] ) ){
                $this->set( 'status_code', 400 );
                throw new NotFoundException(['displayMessage' => 'Error: Your query requires an Order Number'] );
            }

            if( !isset( $inputData['originalOrderNumber'] ) && !isset( $inputData['originalReferenceNumber'] ) ){
                $this->set( 'status_code', 400 );
                throw new NotFoundException(['displayMessage' => 'Error: Your query requires an original Reference Number'] );
            }
        }
        elseif( strtolower( $inputData['type'] ) == 'reversal' || strtolower( $inputData['type'] ) == 'capturewithoutauth' ) {
            if( !isset( $inputData['originalOrderNumber'] ) && !isset( $inputData['originalReferenceNumber'] ) ){
                $this->set( 'status_code', 400 );
                throw new NotFoundException(['displayMessage' => 'Error: Your query requires an original Reference Number'] );
            }
        }

        return true;
    }

    /**
     * Format the input data, with config variables and into the expected format for Westpac
     *
     * @param $inputData
     * @param bool $token
     * @return array
     */
    private function formatInputData( $inputData, $token = true ){

        $transactionConfigs = $this->app->config->get('qvalentTransaction');

        $inputData = $this->removeWestpacSpecialChars($inputData);

        $outputData = array(
            'order.type'           => isset($inputData['type']) ? $inputData['type'] : 'capture',
            'customer.username'    => $transactionConfigs['username'],
            'customer.password'    => $transactionConfigs['password'],
            'customer.merchant'    => $transactionConfigs['merchant'],
            'card.PAN'             => isset($inputData['PAN']) ? $inputData['PAN'] : '',
            'card.CVN'             => isset($inputData['CVN']) ? $inputData['CVN'] : '',
            'card.expiryYear'      => isset($inputData['expYear']) ? $inputData['expYear'] : '',
            'card.expiryMonth'     => isset($inputData['expMonth']) ? $inputData['expMonth'] : '',
            'card.cardHolderName'  => isset($inputData['cardName']) ? $inputData['cardName'] : '',
            'order.amount'         => $inputData['amount'],
            'customer.orderNumber' => $inputData['orderNumber'],
            'card.currency'        => $transactionConfigs['currency'],
            'order.ECI'            => $transactionConfigs['ECI'],
            'customer.originalOrderNumber'
            => isset($inputData['originalOrderNumber']) ? $inputData['originalOrderNumber'] : '',
            'customer.originalReferenceNo'
            => isset($inputData['originalReferenceNo']) ? $inputData['originalReferenceNo'] : '',
            'order.authId'         => isset($inputData['authId']) ? $inputData['authId'] : '',
            'customer.customerReferenceNumber'
            => isset($inputData['custRefNo']) ? $inputData['custRefNo'] : '',
            'customer.preregistrationCode'
            => isset($inputData['preRegCode']) ? $inputData['preRegCode'] : '',
            'order.xid'            => isset($inputData['xid']) ? $inputData['xid'] : '',
            'order.cavv'           => isset($inputData['cavv']) ? $inputData['cavv'] : '',
            'order.ipAddress'      => isset($inputData['ipAddress']) ? $inputData['ipAddress'] : $_SERVER['REMOTE_ADDR'],
        );

        if( $outputData['order.type'] != 'capture' &&
            $outputData['order.type'] != 'refund' &&
            $outputData['order.type'] != 'query' ){
            unset($outputData['customer.orderNumber']);
        }
        if( $outputData['order.type'] != 'capture' &&
            $outputData['order.type'] != 'refund' ){
            unset($outputData['order.ECI']);
        }

        if($token === true){
            //IMPORTANT: At this point, if preregistrationcode is not set, it will not process a payment
            unset($outputData['card.PAN']);
            unset($outputData['card.expiryYear']);
            unset($outputData['card.expiryMonth']);
            unset($outputData['card.CVN']);
        }

        return $outputData;
    }

    /**
     * Removes the three special characters that westpac cannot accept
     *
     * At this stage these consist of & % +
     *
     * @param $input
     * @return array
     */
    private function removeWestpacSpecialChars($input){

        $output = array();
        foreach($input as $key=>$value){
            $output[$key] = preg_replace("/[&%+]/", "", $value);
        }

        return $output;
    }

}