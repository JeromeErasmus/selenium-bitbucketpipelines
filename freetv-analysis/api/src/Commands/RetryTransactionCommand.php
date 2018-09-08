<?php

namespace App\Commands;

use Elf\Core\Module;
use App\Models\Transaction as Transaction;
use App\Models\EloquentModel;


class RetryTransactionCommand extends Module {
    
    public function __construct($app)
    {
        $this->app = $app;
    }
    
     public function execute($args) 
    {
         $this->retryTransaction();
     }
     
    /**
    * retry failed transactions (the ones with summary_code set to 2 that are not have been re-checked two times)
    */
    private function retryTransaction()
    {
        echo "** Retry Failed Transactions **".PHP_EOL;
        $capsule = $this->app->service('eloquent')->getCapsule();
        $transactionModel = new Transaction();
        
        //get the transactions we need to re-process
        $results = $transactionModel->reCheck(array(
            "summaryCode" => $this->app->config->westpacTransactionError,
            "unknownRecheckCount" => $this->app->config->unknownRecheckCountLimit,
        ))->get();
        $transactions = EloquentModel::arrayToRestful($results);

        if(!empty($transactions)){
            try{
                
                //foreach transaction make a new API query
                foreach($transactions as $transaction){
                    $agencyId = $this->getAgencyId($transaction);

                    echo "Process Transaction for agencyId: $agencyId, invoiceId:". $transaction['invoice']['invId']." to be retried".PHP_EOL;
                    $westpacService = $this->app->service('WestpacProcessing');

                    $inputData = $this->formatInputData($transaction);
                    $result = $westpacService->processTransaction($agencyId, $inputData, 1, true);
                }
                
            }catch(\Exception $e){
                echo "Something went wrong.";
                $e->getMessage();
                echo method_exists($e,'getDebugMessage') ? $e->getDebugMessage() : '';
            }
        }
        
        echo "** Finished task **".PHP_EOL;
        return;
    }  
    
    private function getAgencyId($transaction)
    {
        $job = $this->app->model('job');
        $job->setJobId($transaction['invoice']['invJobId']);
        $job->load();
        $jobData = $job->getFullJob();
        return $jobData['agency']['agencyId'];
    }
    
    /**
     * Prepare array for re-processing transaction
     * @param array $data
     * @return array
     */
    private function formatInputData($data)
    {
        $inputData = array();
        $inputData['transactionId'] = $data['transactionId'];
        $inputData['originalOrderNumber'] = $data['invoice']['invId'];
        $inputData['originalReferenceNumber'] = $data['invoice']['invId'];
        $inputData['amount'] = $data['invoice']['invAmountIncGst'];
        $inputData['orderNumber'] = $data['invoice']['invId'];
        
        return $inputData;
    }
}
