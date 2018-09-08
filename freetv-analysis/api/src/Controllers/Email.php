<?php
/**
 * Created by PhpStorm.
 * User: mchan
 * Date: 29/08/16
 * Time: 11:17 AM
 */

namespace App\Controllers;

use Elf\Event\RestEvent;
use Elf\Exception\ConflictException;
use Elf\Exception\MalformedException;
use Elf\Exception\NotFoundException;
use Elf\Http\Request;


class Email extends RestEvent
{
    private $templateName;
    private $htmlVariables;
    private $toAddress;
    private $subject;
    private $mailConfigs;


    public function handleGet(Request $request)
    {
        //TODO return a list of valid emails?
        throw new NotFoundException(['displayMessage' => 'This is not a valid end point']);
    }

    public function handlePatch(Request $request)
    {
        throw new NotFoundException(['displayMessage' => 'This is not a valid end point']);
    }

    public function handleDelete(Request $request)
    {
        throw new NotFoundException(['displayMessage' => 'This is not a valid end point']);
    }

    public function handlePost(Request $request  )
    {
        $inputData = $request->retrieveJSONInput();
        $this->mailConfigs = $this->app->config->get('mail');

        if(empty($inputData['templateName']) || empty($inputData['to']) || empty($inputData['subject']) ){
            throw new NotFoundException(['displayMessage' => 'Invalid data passed']);
        }

        if(empty($inputData['templateType'])){
            $templateType = 'oas';
        }
        else{
            $templateType = $inputData['templateType'];
        }

        $this->setTemplateName($inputData['templateName']);
        if(isset($inputData['htmlVariables'])){
            $this->setHtmlVariables($inputData['htmlVariables']);
        }
        $this->setToAddress($inputData['to']);
        $this->setSubject($inputData['subject']);

        if(!$this->templateCheck($this->getTemplateName(), $templateType)){
            throw new NotFoundException(['displayMessage' => 'You have not selected a valid template']);
        }

        $this->app->service('notifications')->logNotification('Email sent to ' . $inputData['to'] . ' | Subject: ' . $inputData['subject'] );

        $this->app->service('notifications')->processEmail(
            $this->getTemplateName().".$templateType",
            $this->getHtmlVariables(),
            array($this->getToAddress() => $this->getToAddress()),
            array($this->mailConfigs['fromAddr'] => $this->mailConfigs['fromAddr']),
            $this->getSubject()
        );
    }


    /**
     * Checks if the provided template name is available to OAS
     *
     * Must match the following format templateName.email.oas.tpl.php
     *
     * @param $templateName
     * @return bool
     */
    private function templateCheck($templateName, $templateType = 'OAS'){


        if(stristr($templateType, 'OAS')){
            $matchingFiles = glob( $this->mailConfigs['templatePath'] . DIRECTORY_SEPARATOR.'*.oas.tpl.php');
            return $this->OASTemplateCheck($templateName, $matchingFiles);
        }
        return false;
    }

    /**
     * Helper function for templateCheck, lets extension of templateCheck eventually
     *
     * @param $singleFile
     * @param $arrayOfFiles
     * @return bool
     */
    private function OASTemplateCheck($singleFile, $arrayOfFiles){

        $index = array_search($this->mailConfigs['templatePath'] . DIRECTORY_SEPARATOR.$singleFile.'.oas.tpl.php', $arrayOfFiles);

        if(isset($index)){
            return true;
        }

        return false;
    }


    /**
     * @return mixed
     */
    public function getHtmlVariables()
    {
        return $this->htmlVariables;
    }

    /**
     * @param mixed $htmlVariables
     */
    public function setHtmlVariables($htmlVariables)
    {
        $this->htmlVariables = $htmlVariables;
    }

    /**
     * @return mixed
     */
    public function getToAddress()
    {
        return $this->toAddress;
    }

    /**
     * @param mixed $toAddress
     */
    public function setToAddress($toAddress)
    {
        $this->toAddress = $toAddress;
    }

    /**
     * @return mixed
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * @param mixed $subject
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;
    }

    /**
     * @return mixed
     */
    public function getTemplateName()
    {
        return $this->templateName;
    }

    /**
     * @param mixed $templateName
     */
    public function setTemplateName($templateName)
    {
        $this->templateName = $templateName;
    }


}
