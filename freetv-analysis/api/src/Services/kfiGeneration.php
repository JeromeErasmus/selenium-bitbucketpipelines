<?php
/**
 * Created by PhpStorm.
 * User: mchan
 * Date: 24/06/16
 * Time: 8:51 AM
 */

namespace App\Services;

use \ZipArchive as ZipArchive;
use Elf\Core\Module;
use Elf\Exception\NotFoundException;

class kfiGeneration extends Module
{

    private $formattedNewCustomers;
    private $formattedInvoiceData;
    private $configRows = 99; //this is an attache specific max number of rows
    private $documentPath = '';

    private function padOrCut($string, $length, $left = false)
    {
        $string = str_replace(",", "", $string);

        if(strlen($string) < $length){
            $formattedString = str_pad(strtoupper($string), $length, ' ', ($left === false) ? STR_PAD_RIGHT : STR_PAD_LEFT );
        }
        else{
            $formattedString = substr($string,0,$length);
        }
        return $formattedString;

    }

    /**
     * Takes a single row of customer data and formats it for output
     *
     * @param $singleCustomerData
     * @return bool
     */
    private function constructCusRow($singleCustomerData)
    {

        $singleCustomerData['ag_billing_code'] = $this->padOrCut($singleCustomerData['ag_billing_code'], 8);
        $singleCustomerData['ag_name'] = $this->padOrCut($singleCustomerData['ag_name'], 30);
        $singleCustomerData['concatenatedAddress'] = $this->padOrCut($singleCustomerData['concatenatedAddress'], 30);
        $singleCustomerData['ag_city'] = $this->padOrCut($singleCustomerData['ag_city'], 35);
        $singleCustomerData['ag_postcode'] = $this->padOrCut($singleCustomerData['ag_postcode'], 4);
        $singleCustomerData['ag_corp_affair_no'] = isset($singleCustomerData['ag_corp_affair_no']) ? $this->padOrCut($singleCustomerData['ag_corp_affair_no'], 11) : '';
        $singleCustomerData['sta_name'] = $this->padOrCut($singleCustomerData['sta_name'], 3);
        $singleCustomerData['ag_account_type'] = $this->padOrCut($singleCustomerData['ag_account_type'], 3);

        $formattedString = $singleCustomerData['ag_billing_code'].','.
            $singleCustomerData['ag_name'].','.
            $singleCustomerData['concatenatedAddress'].','.
            $singleCustomerData['ag_city'].','.
            $singleCustomerData['ag_postcode'].',,,,,,,'.
            $singleCustomerData['ag_corp_affair_no'].',,,,,,,'.
            $singleCustomerData['ag_account_type'].','.
            '0,,1,,,1,0,'.
            ",,,5,30,,,,".
            $singleCustomerData['sta_name'].','.
            "O,,,AR1,,,,,WBC260883,,,,,,<F9>";

        return $formattedString;
    }

    /**
     * Takes a single row of invoiced data and formats it for output
     *
     * @param $singleInvoiceData
     * @return bool
     */
    private function constructInvRow($singleInvoiceData)
    {
        // If the purchase order is empty, replace it with the first key number on the job
        if (empty($singleInvoiceData['job_purchase_order'])) {
            if (!empty($singleInvoiceData['tvc_key_no'])) {
                $singleInvoiceData['job_purchase_order'] = $singleInvoiceData['tvc_key_no'];
            }
        }
        $singleInvoiceData['ag_billing_code'] = $this->padOrCut($singleInvoiceData['ag_billing_code'], 8);
        $singleInvoiceData['inv_id'] = $this->padOrCut($singleInvoiceData['inv_id'], 8);
        $singleInvoiceData['date_created'] = $this->padOrCut($singleInvoiceData['date_created'], 6);
        $singleInvoiceData['job_reference_no']  = $this->padOrCut($singleInvoiceData['job_reference_no']. ' ' . $singleInvoiceData['job_purchase_order'], 37);
        $singleInvoiceData['inv_amount_inc_gst'] = number_format($singleInvoiceData['inv_amount_inc_gst'], 2);
        $singleInvoiceData['inv_amount_inc_gst'] = $this->padOrCut($singleInvoiceData['inv_amount_inc_gst'],11, true);
        $singleInvoiceData['GST'] = $singleInvoiceData['ag_overseas_gst'] ? 'CADEXPORT' : 'COMMADV';

        $formattedString = $singleInvoiceData['ag_billing_code'].','.
            $singleInvoiceData['inv_id'].','.
            $singleInvoiceData['date_created'].','.
            'TVC APPROVAL   ,'.
            $singleInvoiceData['GST'].','.
            $singleInvoiceData['job_reference_no'].','.
            $singleInvoiceData['inv_amount_inc_gst'].',<F9><ESC><F9>';

        return $formattedString;
    }

    public function createKFIReports($startDate, $endDate)
    {
        if(empty($this->app->config->defaults['paths']['uploadRoot'])){
            $this->documentPath = $this->app->config->defaults['paths']['documentRoot'].'\Uploads\\';
        }else{
            $this->documentPath = $this->app->config->defaults['paths']['uploadRoot'].'\\';
        }

        $kfiModel = $this->app->model('kfiFiles');

        $customerData = $kfiModel->getCustomerData($startDate, $endDate);
		
        $invoiceData = $kfiModel->getInvoiceData($startDate, $endDate);

        if(!empty($customerData)){
            foreach ($customerData as $customer){
                $this->formattedNewCustomers[] = $this->constructCusRow($customer);
            }
        }

        if(!empty($invoiceData)){
            foreach ($invoiceData as $invoice){
                $this->formattedInvoiceData[] = $this->constructInvRow($invoice);
            }
        }

        //generate the documents with the formatted data
        $zipLocation = $this->generateDocuments();

        return $zipLocation;
    }

    /**
     * By default this creates CUS and INV files for KFI reports
     *
     * Creates the ZIP
     *
     * Note: if you do not want to serve a zip file
     *      you will need to call removeFiles after you have served each individual file
     *
     * @param bool $zipFiles
     * @return bool
     */
    private function generateDocuments($zipFiles = true)
    {
        $cadFilename = $this->documentPath . date('dmY').'CADCUS';
        $invFilename = $this->documentPath . date('dmY').'CADINV';
        $customers = $this->formattedNewCustomers;
        $invoices = $this->formattedInvoiceData;
			
        $this->iterateToCreateKFI($customers, $cadFilename);
        $this->iterateToCreateKFI($invoices, $invFilename);

        if($zipFiles === true){
            $matchingFiles = glob($this->documentPath . date('dmY').'*.kfi');

            if(!empty($matchingFiles)){
                $zipLocation = $this->createZIP($matchingFiles, true);
            }
            //cleanup
            $this->removeFiles();
        }
        return $zipLocation;
    }

    /**
     * iterate over the supplied input array and construct files that have a maximum of configRows
     *
     * @param array $inputArray
     * @param string $inputFileName
     */
    private function iterateToCreateKFI($inputArray = array(), $inputFileName = ''){
        $fileCounter = 0;
        $rowsInserted = 1;

        if(empty($inputArray)){
            return true;
        }

        foreach($inputArray as $element){
            $fileName = $inputFileName.sprintf("%02d", $fileCounter).'.kfi';
            $file = fopen( $fileName, 'a');
            if($rowsInserted < $this->configRows){
                fwrite( $file, $element."\n");
                $rowsInserted++;
            }
            else{
                fwrite( $file, $element);
                fclose($file);
                $rowsInserted = 0;
                $fileCounter++;
            }
        }
        fclose($file);
    }

    /**
     * Creates the zip file for the provided files in the files array
     *
     * @param array $files
     * @param bool $overwrite
     * @return bool
     */
    private function createZIP($files = array(), $overwrite = false)
    {
        if(empty($files)){
            return true;
        }

        $zip = new \ZipArchive();

        $destination = $this->documentPath.date('dmY_His').'KFI.zip';

        if($zip->open($destination,$overwrite ? (ZIPARCHIVE::CREATE | ZIPARCHIVE::OVERWRITE) : ZIPARCHIVE::CREATE) !== true) {
            echo 'failed zip';

            return false;
        }
        else{
            foreach($files as $file){
                $zippedFile = str_replace($this->documentPath, '', $file);
                $zip->addFile($file, $zippedFile);
            }
            $zip->close();
        }

        return $destination;
    }

    /**
     * This function cleans up after createZIP has run
     * It does not take any files in as arguments as we are trying to be as safe as possible
     *
     * Don't want to delete ~/ now do we?
     */
    private function removeFiles()
    {
        $matchingFiles = glob($this->documentPath."/".date('dmY').'*.kfi');

        foreach($matchingFiles as $file){
            unlink($file);
        }
    }

}