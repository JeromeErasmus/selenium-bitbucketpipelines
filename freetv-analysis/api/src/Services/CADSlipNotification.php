<?php
/**
 * Created by PhpStorm.
 * User: thimira.gunasekera
 * Date: 22/01/2016
 * Time: 9:27 AM
 *
 * Created a separate notification service for CAD slips as it's not just a generic notification
 */

namespace App\Services;

use Elf\Core\Module;

class CADSlipNotification extends Module
{
    private $mail;

    public function init()
    {
        $this->mail = $this->app->service('Mail');

    }

    public function notifyContacts($jobId)
    {
        $variables = [
            'ref_number' => '12345678',
            'agency_name' => 'My Cool agency',
            'agency_fax' => '',

        ];

        $capsule = $this->app->service('eloquent')->getCapsule();
        $model = \App\Models\AdviceSlipTemplate::findOrFail(1);
        $htmlTemplate =  $model->getAsArray()['adviceTemplate'];

        $parsedHtml = $this->parseTemplate($htmlTemplate, $variables);
        $parsedHtml = preg_replace('/(<img.*?src=[\'"])(.*?)([\'"].*?>)/i', '$1img_1$3', $parsedHtml);      //replace the logo url with the one below

        /* now get a list of contacts to send to */

        $to = [];
        /* now send to each of these people */
        $this->mail->sendHtmlMessage(
            $to,
            $this->app->config->get('mail')['fromAddr'],
            'Test - CAD Advice Slip',
            $parsedHtml,
            ['img_1' => $this->app->config->get('mail')['templatePath'].'/freetv_logo.png']
        );

    }

    /**
     * @param $templateHtml
     * @param $variables
     * @return mixed
     *
     * parse all {%variableName%}'s and replace with corresponding variableName in $variables array
     *
     */
    private function parseTemplate($html, $variables = array())
    {
        if(preg_match_all('/{%(.*?)%}/', $html, $arr) > 0) {
            foreach($arr[1] as $replace) {      //go through all variables in the template
                if (isset($variables[$replace])) {
                    $html = str_replace("{%$replace%}", $variables[$replace], $html); //if substitution exists, replace
                } else {
                    $html = str_replace("{%$replace%}", '', $html);      //else get rid of the {%xxxx%} things
                }
            }
        }
        return $html;
    }

    private function replaceImgLocations(&$html) //@todo.....
    {

    }
}