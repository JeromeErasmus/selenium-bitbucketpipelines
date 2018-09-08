<?php

namespace App\Services;

use \ZipArchive as ZipArchive;
use Elf\Core\Module;
use Elf\Exception\NotFoundException;

class XeroFilesGeneration extends Module
{
    private $documentPath = '';

    const CUSTOMERS_TYPE = 1;
    const INVOICES_TYPE = 2;

    /**
     * Header for invoice file
     */
    const INVOICE_HEADERS = [
        '*ContactName',
        'EmailAddress',
        'POAddressLine1',
        'POAddressLine2',
        'POAddressLine3',
        'POAddressLine4',
        'POCity',
        'PORegion',
        'POPostalCode',
        'POCountry',
        '*InvoiceNumber',
        'Reference',
        '*InvoiceDate',
        '*DueDate',
        'InventoryItemCode',
        '*Description',
        '*Quantity',
        '*UnitAmount',
        'Discount',
        '*AccountCode',
        '*TaxType',
        'TrackingName1',
        'TrackingOption1',
        'TrackingName2',
        'TrackingOption2',
        'Currency',
        'BrandingTheme'
    ];

    /**
     * Header for customer file
     */
    const CUSTOMERS_HEADERS = [
        '*ContactName',
        'AccountNumber',
        'EmailAddress',
        'FirstName',
        'LastName',
        'POAttentionTo',
        'POAddressLine1',
        'POAddressLine2',
        'POAddressLine3',
        'POAddressLine4',
        'POCity',
        'PORegion',
        'POPostalCode',
        'POCountry',
        'SAAttentionTo',
        'SAAddressLine1',
        'SAAddressLine2',
        'SAAddressLine3',
        'SAAddressLine4',
        'SACity',
        'SARegion',
        'SAPostalCode',
        'SACountry',
        'PhoneNumber',
        'FaxNumber',
        'MobileNumber',
        'DDINumber',
        'SkypeName',
        'BankAccountName',
        'BankAccountNumber',
        'BankAccountParticulars',
        'TaxNumber',
        'AccountsReceivableTaxCodeName',
        'AccountsPayableTaxCodeName',
        'Website',
        'Discount',
        'CompanyNumber',
        'DueDateBillDay',
        'DueDateBillTerm',
        'DueDateSalesDay',
        'DueDateSalesTerm',
        'SalesAccount',
        'PurchasesAccount',
        'TrackingName1',
        'SalesTrackingOption1',
        'PurchasesTrackingOption1',
        'TrackingName2',
        'SalesTrackingOption2',
        'PurchasesTrackingOption2',
        'BrandingTheme',
        'DefaultTaxBills',
        'DefaultTaxSales'
    ];

    /**
     * Create Xero reports
     * @param $startDate
     * @param $endDate
     * @return bool
     */
    public function createXeroReports($startDate, $endDate)
    {
        // Set document path
        if (empty($this->app->config->defaults['paths']['uploadRoot'])) {
            $this->documentPath = $this->app->config->defaults['paths']['documentRoot'] . '\Uploads\\';
        } else {
            $this->documentPath = $this->app->config->defaults['paths']['uploadRoot'] . '\\';
        }

        $xeroModel = $this->app->model('XeroFiles');

        // Get customer data
        $customerData = $xeroModel->getCustomerData();

        // Get invoice data
        $invoiceData = $xeroModel->getInvoiceData($startDate, $endDate);

        // Generate the documents with the formatted data
        $zipLocation = $this->generateDocuments($customerData, $invoiceData, true);

        return $zipLocation;
    }

    /**
     * Generate Xero documents
     * @param $customerData
     * @param $invoiceData
     * @param bool $zipFiles
     * @return bool|null
     */
    private function generateDocuments($customerData, $invoiceData, $zipFiles = true)
    {
        // Customers file
        $this->generateCustomersDocument($customerData);

        // Invoices file
        $this->generateInvoicesDocument($invoiceData);

        if ($zipFiles === true) {
            return $this->compressFiles();
        }

        return true;
    }

    /**
     * Generate customers document
     * @param $customerData
     * @return bool
     */
    private function generateCustomersDocument($customerData)
    {
        // Set filename
        $filename = $this->documentPath . date('dmY') . 'CADCUS';

        // Create file
        $this->iterateToCreateFile(self::CUSTOMERS_TYPE, $customerData, $filename);

        return true;
    }

    /**
     * Generate invoices document
     * @param $invoiceData
     * @return bool
     */
    private function generateInvoicesDocument($invoiceData)
    {
        // Set filename
        $filename = $this->documentPath . date('dmY') . 'CADINV';

        // Create file
        $this->iterateToCreateFile(self::INVOICES_TYPE, $invoiceData, $filename);

        return true;
    }

    /**
     * Create CSV file
     * @param $fileType
     * @param array $inputArray
     * @param string $inputFileName
     * @return bool
     */
    private function iterateToCreateFile($fileType, $inputArray = array(), $inputFileName = '')
    {
        $fileCounter = 0;

        if ( empty($inputArray) ) {
            return true;
        }

        // Create headers
        if ($fileType == self::CUSTOMERS_TYPE) {
            $header = self::CUSTOMERS_HEADERS;
        } else if ($fileType == self::INVOICES_TYPE) {
            $header = self::INVOICE_HEADERS;
        }
        else {
            $header = null;
        }

        $data = array();

        // Add header
        $data[] = $header;

        // Remove extra characters from header so they match the results from SQL query and we can merge
        $header = $this->removeExtraCharacters($header);

        foreach ($inputArray as $anInput) {
            // Create empty array with key values equal to header
            $emptyRow = array_fill_keys($header, "");
            $newRow = array_merge($emptyRow, $anInput);
            $data[] = $newRow;
        }


        $fileName = $inputFileName . sprintf("%02d", $fileCounter) . '_XERO.csv';

        $fp = fopen($fileName, 'w');

        foreach ($data as $fields) {
            fputcsv($fp, $fields);
        }

        fclose($fp);

        return true;

    }

    /**
     * Remove extra characters
     * @param $data
     * @return mixed
     */
    private function removeExtraCharacters($data)
    {
        foreach ( $data as $key => $value ) {
            $data[$key] = str_replace("*", "", $data[$key]);
        }

        return $data;
    }

    /**
     * Compress files
     * @return bool|null
     */
    private function compressFiles()
    {
        $matchingFiles = glob($this->documentPath . date('dmY').'*_XERO.csv');

        if ( !empty($matchingFiles) ) {
            $zipLocation = $this->createZIP($matchingFiles, true);
        }
        else {
            $zipLocation = null;
        }

        // Cleanup
        $this->removeFiles();

        return $zipLocation;
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

        $destination = $this->documentPath.date('dmY_His').'Xero.zip';

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
        $matchingFiles = glob($this->documentPath."/".date('dmY').'*_XERO.csv');

        foreach($matchingFiles as $file){
            unlink($file);
        }
    }

}