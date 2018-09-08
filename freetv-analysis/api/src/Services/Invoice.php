<?php

namespace App\Services;

use App\Models\Invoice as Model;
use App\Models\KeyNumber;
use Elf\Core\Module;
use Elf\Exception\ServerException;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;


class Invoice extends Module {

    private $inviceType;

    /**
     *  Create an invoice entry in the database and add the invoiceid to the tvckeynumbers
     *
     * @param $params
     * @param $keyNumbersArray
     * @return mixed
     */
    public function create($params, $keyNumbersArray)
    {
        $invoiceId = $this->invoice($params, $keyNumbersArray);

        return $invoiceId;
    }

    /**
     * Create the invoice document
     *
     * @param $invoiceId
     * @param $params
     * @return mixed
     *
     */
    public function createInvoiceDocument($invoiceId, $params)
    {
        if(!empty($invoiceId)){
            //create document
            $params['documentParams']['invoice_number'] = $invoiceId;
            $document = $this->document($params['documentParams']);

            if(!empty($document['documentId'])){
                // remove all unnecessary char (i.e. commas)
                $params['documentParams']['grand_total'] = preg_replace('/[^\d.]/', '', $params['documentParams']['grand_total']);
                $params['documentParams']['gst_total'] = preg_replace('/[^\d.]/', '', $params['documentParams']['gst_total']);

                //insert the document id into invoices table
                $updateParams['invoiceId'] = $invoiceId;
                $updateParams['documentId'] = $document['documentId'];
                $updateParams['locationUrl'] = $document['locationUrl'];
                $updateParams['amountExGst'] = $params['documentParams']['grand_total'] - $params['documentParams']['gst_total'];
                $updateParams['gst'] = $params['documentParams']['gst_total'];
                $updateParams['amountIncGst'] = $params['documentParams']['grand_total'];
                return $this->invoice($updateParams);
            }
        }
        throw new ServerException("Document generation failure");
    }

    /**
     * generate a document, in the process it inserts a record in the job_upload_material table
     * @param type $params
     * @return array
     */
    public function document($params)
    {
        //generate the document
        $pdfDocumentGeneration = $this->app->service('pdfDocumentGeneration');
        $pdfDocumentGeneration->setFileName('Invoice_' . $params['invoice_number']);

        return $pdfDocumentGeneration->generateDocument('invoice',$params);
    }

    /**
     * create/update a record in the invoice table then record the id to each tvc
     * @param $invoiceParams
     * @param array $keyNumbersArray
     * @return mixed
     */
    public function invoice($invoiceParams, $keyNumbersArray = [])
    {
        // Please note that "Model" is an Invoice model. See the use statement at the top of this file. This is
        // a very bad naming convention and needs to be changed but for consistency we shall go with it.

        $this->app->service('eloquent')->getCapsule();

        // If an invoice ID was not specified, check to see if there are any "broken invoices" that we can continue with.
        // @see FREETV-1277
        if(!isset($invoiceParams['invoiceId']) && isset($invoiceParams['jobId'])) {
            /** @var \App\Models\Invoice $brokenInvoice */
            $brokenInvoice = Model::where('inv_job_id','=',$invoiceParams['jobId'])
                ->whereNull('inv_amount_ex_gst')
                ->whereNull('inv_tra_id')
                ->first();
        }

        if(!empty($brokenInvoice)) {
            // Work with arrays to maintain consistency
            $brokenInvoiceArr = $brokenInvoice->getAsArray();

            // get the existing one
            $model = Model::findOrFail($brokenInvoiceArr['id']);
            $invoiceData = $model->getAsArray();

            // Set the invoice blob from the DB
            $invoiceParams['invoiceBlob'] = $invoiceData['invoiceBlob'];
        }
        // This is not a broken invoice scenario so continue as per normal.
        else {
            // Create a new invoice if no ID was provided
            if(empty($invoiceParams['invoiceId'])){
                //new model
                $model = new Model();
                $invoiceParams['invoiceBlob'] = json_encode($invoiceParams);

            }
            // Try to find the invoice belonging to the specified invoice ID.
            else {
                //update existing one
                $model = Model::findOrFail($invoiceParams['invoiceId']);
                $invoiceData = $model->getAsArray();

                if( !empty($invoiceData['invoiceBlob']) ){
                    $invoiceParams['invoiceBlob'] = array_merge($invoiceParams, array('invoiceBlob' => $invoiceData['invoiceBlob']));
                }
                $invoiceParams['invoiceBlob'] = json_encode($invoiceParams);
            }
        }

        if(!empty($invoiceParams['amountExGst'])){
            $invoiceParams['amountExGst'] = floatval(preg_replace('/[^\d.]/', '', $invoiceParams['amountExGst']));;
        }
        if(!empty($invoiceParams['amountIncGst'])){
            $invoiceParams['amountIncGst'] = floatval(preg_replace('/[^\d.]/', '', $invoiceParams['amountIncGst']));
        }
        if(!empty($invoiceParams['grand_total'])){
            $invoiceParams['grand_total'] = floatval(preg_replace('/[^\d.]/', '', $invoiceParams['grand_total']));
        }

        $model->setFromArray($invoiceParams);
        $model->save();
        $invoiceParams['id'] = $model->id;

        foreach($keyNumbersArray as $key) {

            $keyNumberModel = $this->app->model('KeyNumber');

            $keyNumberModel->getTvcById($key['tvcId']);
            $keyNumberData['tvcInvoiceId'] = $invoiceParams['id'];
            $keyNumberModel->setFromArray($keyNumberData);
            $keyNumberModel->save();

        }
        return $invoiceParams;
    }

}