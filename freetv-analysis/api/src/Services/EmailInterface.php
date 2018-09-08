<?php
/**
 * Created by PhpStorm.
 * User: thimira.gunasekera
 * Date: 14/01/2016
 * Time: 9:10 AM
 */

namespace App\Services;

interface EmailInterface
{
    public function sendSimpleMessage($to = array(), $from, $subject, $message);

    /**
     * @param array $to
     * @param $from
     * @param $subject
     * @param $html
     * @return mixed
     *
     * Send html email to recipients. Recipients in the format of [['email' => 'displayName'],[...],[...], etc]
     */
    public function sendHtmlMessage( $to = array(), $from, $subject, $html, $imageUrls = array() );

}