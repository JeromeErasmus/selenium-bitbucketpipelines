<?php
/**
 * Created by PhpStorm.
 * User: jeremy.paul
 * Date: 21-Jun-16
 * Time: 4:47 PM
 */

namespace App\Services;


use App\Models\Notification;

class KeyNumberNotifications extends Notifications
{
    public function test()
    {
        $this->withdrawnCadNumber($this->mailConfig['testTvcId']);

    }
    public function withdrawnCadNumber($tvcId)
    {
        $keyNumber = $this->getKeynumber($tvcId);
        $jobId = $keyNumber['jobId'];
        $this->jobId = $jobId;
        $job = $this->getJob($jobId);
        $advertiser = $this->getAdvertiser($job->getAdvertiserId());
        $jobContacts = $this->getAllJobContacts($jobId,true);

        foreach($jobContacts as $contacts) {
            foreach ($contacts as $contact) {
                if (empty($contact['email'])) {
                    continue;
                }
                $htmlVariables = [
                    'firstname' => !empty($contact['name']) ? $contact['name'] : '',
                    'jobId' => $jobId,
                    'withdrawnCadNumber' => $keyNumber['cadNumber'],
                    'keyNumber' => $keyNumber['keyNumber'],
                ];

                $this->processEmail('withdrawncadnumber.email', $htmlVariables, [$contact['email'] => $contact['name']],
                    [$this->getFromAddr() => $this->fromName], "{$keyNumber['keyNumber']}  has had it's CAD number withdrawn, {$jobId}, " . $advertiser['advertiserName']);

            }
        }
    }

    public function getKeyNumber($tvcId)
    {
        $keyNumberModel = $this->app->model('KeyNumber');
        $keyNumberModel->setTvcId($tvcId);
        $keyNumberModel->load();
        return $keyNumberModel->getAsArray();
    }

    public function getFullJob($jobId)
    {
        $job = $this->app->model('job');
        $job->setJobId($jobId);
        $job->load();
        return $job->getFullJob();
    }
}