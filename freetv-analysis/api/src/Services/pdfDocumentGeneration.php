<?php
/**
 * Created by PhpStorm.
 * User: mchan
 * Date: 30/05/16
 * Time: 10:44 AM
 */

namespace App\Services;

use Elf\Event\AbstractEvent;
use Elf\Exception\NotFoundException;
use mPDF;

/**
 *
 *
 * Example Usage:   $documentService = $this->app->service('pdfDocumentGeneration');
            echo $documentService->generateDocument('invoice',$invoiceData);
 *
 *
 * Class pdfDocumentGeneration
 * @package App\Services
 */

class pdfDocumentGeneration extends AbstractEvent
{
    private $templatePath;
    private $selectedTemplateGroup;
    private $pdfGenerator;
    private $documentUpload;
    private $documentModel;
    private $cadAdviceLogoPath;
    private $logoPath;
    private $jobId;
    private $fileName;
    private $pdfType;

    protected $fieldMap = array (
        'invoice' => array(
            'primary' => 'invoice.tpl.html',
            'secondary' => 'invoice-items.tpl.html',
            'fileName' => 'invoice.pdf',
            'footer' => 'invoice'
        ),
        'originalOrderForm' => array(
            'primary' => 'order-form.tpl.html',
            'secondary' => 'order-form-items.tpl.html',
            'fileName' => 'order_form.pdf',
            'header' => 'order-form-header.tpl.html',
            'footer' => false,
        ),
        'cadAdviceSlip' => array(
            'primary' => 'cad-advice-slip.tpl.html',
            'secondary' => 'cad-advice-slip-items.tpl.html',
            'fileName' => 'cad_advice_slip.pdf',
            'header' => 'cad-advice-slip-header.tpl.html',
            'footer' => false,
        ),
    );

    public function default_event()
    {
        $this->_construct();
    }

    public function _construct($options = array())
    {

        foreach($options as $key => $option){
            $this->key = $option;
        }

        if(empty($this->templatePath) || !is_dir($this->templatePath)){
            $docGen = $this->app->config->get('documentGeneration');
            $this->templatePath = $docGen['templatePath'];
            $this->logoPath = $docGen['logoPath'];
            $this->cadAdviceLogoPath = $docGen['cadAdviceLogoPath'];
        }

        $this->documentModel = $this->app->model('Document');

        if($this->pdfType == 'invoice'){
            //default values for mpdf, we need to remove the margin top particularly for invoices by setting it to 0
            $this->pdfGenerator = new MPDFExtension(3);
        } else {
            $this->pdfGenerator = new MPDFExtension();
        }

        $this->documentUpload = $this->app->service('documentUpload');

    }


    /**
     * The template provided must match one of the keys in the above fieldmap or the generation will fail
     *
     * @param $template
     * @param array $data
     * @param bool $as_stream
     * @return mixed
     * @throws NotFoundException
     * @throws \Exception
     */
    public function generateDocument($template, $data = array(), $as_stream = true)
    {
        // Start logging
        $loggingService = $this->app->service('Logger');
        $loggingService->setFilename('ConfirmFinalOrderForm');
        $loggingService->info('pdfDocumentGeneration::generateDocument');

        $this->pdfType = $template;

        $this->_construct();

        if(!isset($data['jobId']) && !isset($data['referenceNo'])){
            $loggingService->error('pdfDocumentGeneration::generateDocument Required Job Id not provided');
            throw new \Exception ('Required Job Id not provided');
        }
        $this->jobId = $data['jobId'];
        $template = $this->checkTemplate($template);

        //Reference the following for output formatting
        //https://mpdf.github.io/reference/mpdf-functions/output.html
        if($as_stream){
            //String
            $as_stream = 'S';
        }else{
            //Force Download
            $as_stream = 'F';
        }

        if(is_file($template['primary']) && is_readable($template['primary'])){
            $html = $this->processTemplate($template, $data);
            if(!empty($this->pdfGenerator)){
                $this->pdfGenerator->WriteHTML($html);

                $content = $this->pdfGenerator->Output('', $as_stream);
                $content = chunk_split(base64_encode($content));

                $date = new \DateTime('now');

                $inputData['fileName'] = $template['fileName'];

                $inputData = $this->documentUpload->constructDocumentArray($data, $content);

                if( !empty( $this->fileName ) ) {
                    $inputData['fileName'] = $this->fileName;
                }
                else if($inputData['fileName'] == NULL){
                    $inputData['fileName'] = $inputData['jobId'];
                }


                $inputData['jobId'] = $data['jobId'];
                $filename = $inputData['fileName'] . "-" . $date->format("d-m-Y-H-i-s") . ".pdf";
                $inputData['systemFileName'] = $filename;

                $inputData['fileName'] = $inputData['fileName'] . ".pdf";

                try {
                    $loggingService->info('pdfDocumentGeneration::generateDocument Creating file. Input data job ID: ' . $inputData['jobId']);

                    $file = $this->documentUpload->documentCreateFile($inputData);
                    $loggingService->info('pdfDocumentGeneration::generateDocument File created: ' . json_encode($file));

                    $inputData['systemFilename'] = $file['url'];
                    $userId = $this->app->service('user')->getCurrentUser()->getUserSysId();
                    $inputData['userId'] = $userId;
                    $documentTypes = $this->app->config->get('documentTypes');
                    $inputData['uploadTypeId'] = $documentTypes['systemDocument'];
                    if(empty($file['local'])) {
                        $inputData['fileIsS3Link'] = true;
                    }
                    $this->documentModel->setFields($inputData); // reset the new fields after create the file.
                    $documentId = $this->documentModel->save();
                    $loggingService->info('pdfDocumentGeneration::generateDocument Document ID: ' . $documentId);

                    $result = array(
                        'status_code' => 201,
                        'locationUrl' => 'cad/document/documentId/' . $documentId,
                        'documentId' => $documentId
                    );
                    return $result;
                }
                catch(\Exception $exception){
                    $loggingService->error('pdfDocumentGeneration::generateDocument Exception: ' . $exception->getMessage());
                    throw $exception;
                }

            }
            $loggingService->error('pdfDocumentGeneration::generateDocument No PDF generator specified');
            throw new NotFoundException("No PDF generator specified");
        }
        else{
            $loggingService->error('pdfDocumentGeneration::generateDocument The ' . $template['primary'] . ' you have tried to access does not exist');
            throw new NotFoundException("PDFGen: The {$template['primary']} you have tried to access does not exist");
        }
    }

    public function processTemplate($template, $data = array())
    {
        $templateOutput = '';
        if(is_file($template['header'])) {
            $templateOutput .= file_get_contents($template['header']);
        }
        $templateOutput .= file_get_contents($template['primary']);
        if(is_file($template['footer'])) {
            $templateOutput .= file_get_contents($template['footer']);
        }

        preg_match_all('~\{\%(\w+)\%\}~', $templateOutput, $matches);

        foreach($matches[0] as $key => $match){
            if($match === '{%logo%}'){
                $templateOutput = str_replace($match, $this->logoPath, $templateOutput);
            }

            if($match === '{%cadAdviceSlipLogo%}'){
                $templateOutput = str_replace($match, $this->cadAdviceLogoPath, $templateOutput);
            }

            if($match === '{%template_items%}') {
                $items = "";
                foreach($data['items'] as $item) {
                    $rowTemplate = file_get_contents($template['secondary']);
                    preg_match_all('~\{\%(\w+)\%\}~', $rowTemplate, $rowMatches);
                    foreach($rowMatches[0] as $rowKey => $rowMatch) {
                        $rowTemplate = str_replace($rowMatch, $item[$rowMatches[1][$rowKey]], $rowTemplate);
                    }
                    $items .= $rowTemplate;
                }
                $templateOutput = str_replace($match, $items, $templateOutput);

            } else {
                $templateOutput = str_replace($match, $data[$matches[1][$key]], $templateOutput);
            }
        }

        return $templateOutput;
    }

    private function checkTemplate($template){
        if(array_key_exists($template, $this->fieldMap)){
            $this->selectedTemplateGroup = $this->fieldMap[$template];

            $templates['primary'] = $this->templatePath . $this->selectedTemplateGroup['primary'];

            if(array_key_exists('secondary', $this->fieldMap[$template])){
                $templates['secondary'] = $this->templatePath . $this->selectedTemplateGroup['secondary'];
            }

            // To allow for custom headers
            if(!empty($this->fieldMap[$template]['header'])) {
                $templates['header'] = $this->templatePath . $this->fieldMap[$template]['header'];
            } elseif($this->fieldMap[$template]['header'] === false) {
                $templates['header'] = '';
            } else {
                $templates['header'] = $this->templatePath . 'header.tpl.html';
            }
            if($this->fieldMap[$template]['footer'] === 'invoice') {
                $templates['footer'] = $this->templatePath . 'footer.tpl.html';
                $this->pdfGenerator->setFooter("Page {PAGENO} of {nb}");
            } elseif(!empty($this->fieldMap[$template]['footer'])) {
                $templates['footer'] = $this->templatePath . $this->fieldMap[$template]['footer'];
            } elseif($this->fieldMap[$template]['footer'] === false) {
                $templates['footer'] = '';
            } else {
                $templates['footer'] = '';
            }


            //This sets the document filenames
            $templates['fileName'] = !empty($this->fileName) ? $this->fileName : $this->jobId.'-'.$this->fieldMap[$template]['fileName'];

            return $templates;
        }
        throw NotFoundException("The filetype you are trying to access does not exist");
    }

    public function setFileName($fileName)
    {
        $this->fileName = $fileName;
    }

}