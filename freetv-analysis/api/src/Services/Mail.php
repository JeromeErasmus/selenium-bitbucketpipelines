<?php
/**
 * Created by PhpStorm.
 * User: thimira.gunasekera
 * Date: 13/01/2016
 * Time: 11:09 AM
 */

namespace App\Services;

use Elf\Core\Module;
use App\Models\Document as documentModel;

class Mail extends Module implements EmailInterface
{
    private $mailConfig;
    private $mailer;
    private $attachments;
    private $productionUrl;

    /**
     * Requires correct config variables to be set
     */
    public function init()
    {
        $this->mailConfig = $this->app->config->get('mail');
        $transport = \Swift_SmtpTransport::newInstance($this->mailConfig['host'], $this->mailConfig['port'])
            ->setUsername($this->mailConfig['username'])
            ->setPassword($this->mailConfig['password']);
        $this->productionUrl = $this->app->config->get('productionUrl');
        $this->mailer = \Swift_Mailer::newInstance($transport);
    }


    public function sendSimpleMessage($to = array(), $from = array(), $subject, $message) {

        if (empty($to) || empty($from) || empty($message)) {
            throw new \Exception("Missing fields in email");
        }

        $message = \Swift_Message::newInstance()
            ->setSubject($subject)
            ->setFrom($from)
            ->setTo($to)
            ->setBody($message);

        $result = $this->mailer->send($message);

        if ($result === false) {
            throw new Exception("Failed to send email");
        }
        var_dump($result);
    }


    public function sendHtmlMessage( $to = array(), $from = array(), $subject, $html, $imageUrls = array(), $attachmentPath = false )
    {
        if (empty($to) || empty($from) || empty($subject)) {
            throw new \Exception("Missing email fields");
        }
        $message = \Swift_Message::newInstance($subject);

        $attachmentsArray = $this->getAttachments();

        if(!empty($attachmentsArray && !is_array($attachmentsArray) )){
            throw new \Exception("Invalid attachments format");
        }
        $filesToDelete = array();
        if($attachmentPath === true){
            foreach($attachmentsArray as $attachment) {
                $emailAttachment = \Swift_Attachment::fromPath($attachment);
                $message->attach($emailAttachment);
            }
        }else if(!empty($attachmentsArray)){
            foreach($attachmentsArray as $attachment){
                //NOTE: This will in all cases of attaching local files to an email
                // HOWEVER this will not work for:
                // URL documents on servers with allow_url_fopen set to false ie, documents on s3buckets where php.ini is not set correctly on our server

                //retrieve the documents based on the ID passed in aas $attachment
                $document = new documentModel($this->app);
                $document->setDocumentId($attachment);
                $documentDetails = $document->load();
                $fileName = $documentDetails[0]['jum_system_filename'];
                if($documentDetails[0]['jum_is_s3_link']) {
                    $fileName = $this->retrieveS3FileName($documentDetails[0]['jum_system_filename']);
                    $filesToDelete[] = $fileName;
                    $emailAttachment = \Swift_Attachment::fromPath($fileName);
                    $message->attach($emailAttachment);
                } else {
                    $emailAttachment = \Swift_Attachment::fromPath($fileName);
                    $message->attach($emailAttachment);
                }
            }
        }

        // embed all the images by searching for $replace and replacing with the embedded url with the image url $url
        if (!empty($imageUrls) ) {
            foreach($imageUrls as $replace => $url) {
                $embedUri = $message->embed(\Swift_Image::fromPath($url));
                $html = str_replace($replace, $embedUri, $html);
            }
        }

        $message->setBody($html,'text/html');
        $message->setFrom($from);

        $failedRecipients = array();
        $numSent = 0;

        foreach ($to as $address => $name)
        {
            if (is_int($address)) {
                $message->setTo($name);
            } else {
                $message->setTo(array($address => $name));
            }
            $numSent += $this->mailer->send($message, $failedRecipients);
        }

        foreach ($filesToDelete as $fileName) {
            unlink($fileName);
        }

        if ($numSent != count($to)) {
            throw new \Exception("Failed to email recipients: ".implode(',',$failedRecipients));
        }
    }

    /**
     * Retrieves file from AWS, stores locally so it can be attached to an email
     * Gets deleted at the end of the function above
     * @param $systemFileName
     * @return string
     */
    private function retrieveS3FileName($systemFileName)
    {
        $fileNameComponents = explode('/',$systemFileName);

        $s3  = $this->app->service('s3connector');

        preg_match("/" . $this->app->config->get('s3connector')['s3']['bucket'] . "\/(.*)/", $systemFileName, $output_array);

        $fileName = $this->app->config->get('paths')['documentRoot'] . DIRECTORY_SEPARATOR . 'Uploads' . DIRECTORY_SEPARATOR . end($fileNameComponents);
        $result = $s3->getFile($output_array[1]);
        file_put_contents($fileName,(string) $result['Body']);
        return $fileName;
    }

    /**
     * @param $template
     * @param $htmlVariables
     * @return string
     * @throws \Exception
     *
     * Loads an HTML from a file
     *
     */
    public function loadHtml($template, $htmlVariables = array())
    {
        if (empty($template)) {
            throw new \Exception("No email template specified");
        }
        if(empty($htmlVariables['productionUrl']))
        {
            $htmlVariables['productionUrl'] = $this->productionUrl;
        }
        foreach($htmlVariables as $key => $val) {       //set all the template variables
            $$key = $val;
        }

        $fnFilename = $this->config['templatePath'] . DIRECTORY_SEPARATOR.$template.'.tpl.php';
        if (is_file( $fnFilename ) === true) {
            $fnOriginalSetting = ini_get('display_errors');
            ini_set('display_errors', false);
            ob_start();
            include( $fnFilename );
            ini_set('display_errors', $fnOriginalSetting);
            return ob_get_clean();
        } else {
            throw new \Exception("Email template not found");
        }
    }


    /**
     * @return mixed
     */
    public function getTextBody()
    {
        return $this->textBody;
    }

    /**
     * @param mixed $textBody
     */
    public function setTextBody($textBody)
    {
        $this->textBody = $textBody;
    }

    /**
     * @return mixed
     */
    public function getHtmlBody()
    {
        return $this->htmlBody;
    }

    /**
     * @param mixed $htmlBody
     */
    public function setHtmlBody($htmlBody)
    {
        $this->htmlBody = $htmlBody;
    }

    /**
     * @return mixed
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * @param mixed $template
     */
    public function setTemplate($template)
    {
        $this->template = $template;
    }

    /**
     * This should be an array of document paths
     *
     * ie. [
     *          ['s3link'],
     *          ['localPath'],
     *      ]
     *
     * @param mixed $attachmentArray
     */
    public function setAttachments($attachmentArray)
    {
        $this->attachments = $attachmentArray;
    }

    public function getAttachments(){
        return $this->attachments;
    }



}