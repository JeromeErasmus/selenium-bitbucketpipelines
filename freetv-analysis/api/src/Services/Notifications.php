<?php
/**
 * Created by PhpStorm.
 * User: thimira.gunasekera
 * Date: 14/01/2016
 * Time: 10:05 AM
 */
namespace App\Services;

use App\Models\Advertiser;
use App\Models\Comment;
use App\Models\Contact;
use App\Models\Job;
use Elf\Core\Module;
use Elf\Exception\NotFoundException;

class Notifications extends Module
{
    public $mail;
    public $fromAddr;
    public $fromName;
    public $mailConfig;
    public $notificationConfig;
    public $jobId;

    public function init()
    {
        $this->mail = $this->app->service('Mail');
        $this->mailConfig = $this->app->config->get('mail');
        $this->fromAddr = $this->mailConfig['fromAddr'];
        $this->fromName = $this->mailConfig['fromName'];
        $this->notificationConfig = $this->app->config->get('notifications');
        $this->app->service('eloquent')->getCapsule();

    }

    public function test()
    {
        $this->notifyJobStatusChange($this->mailConfig['testJobId']);
        $this->notifyRedHotSubmission($this->mailConfig['testJobId'], $this->mailConfig['testAdvertiserId']);
        $this->notifyAdditionalCharge($this->mailConfig['testJobId'], json_encode([$this->mailConfig['testDocument']]));
        $this->notifyPaymentFailure($this->mailConfig['testJobId']);
        $this->notifyCadIssued($this->mailConfig['testJobId'],json_encode([$this->mailConfig['testDocument']]));
        $this->notifyCadIssued($this->mailConfig['testPrecheckId'],json_encode([$this->mailConfig['testDocument']]));
        $this->notifyOrderForm($this->mailConfig['testJobId'],json_encode([$this->mailConfig['testDocument']]));
        $this->notifyOrderForm($this->mailConfig['testPrecheckId'],json_encode([$this->mailConfig['testDocument']]));
    }

    public function notifyJobStatusChange($jobId)
    {
        $this->jobId = $jobId;
        $job = $this->getJob($jobId);
        $advertiser = $this->getAdvertiser($job->getAdvertiserId());
        $this->logNotification('Mail out started (notify job status change) '.date('d-m-y h:i:s', time()));
        $mail = $this->app->service('Mail');
        $allContacts = $this->getAllJobContacts($jobId);
        foreach($allContacts as $contacts) {
            foreach ($contacts as $contact) {
                if (!empty($contact['email'])) {
                    $html = $mail->loadHtml('awaitingagencyfeedback.email', ['jobId' => $jobId]);
                    try {
                        $mail->sendHtmlMessage([
                            $contact['email'] => $contact['name']],
                            [$this->fromAddr => $this->fromName],
                            $this->getRedHotSubjectLine() ."Job Updated - Reference No : {$jobId}, " . $advertiser['advertiserName'],
                            $html
                        );
                        $this->logNotification("Sent mail to {$contact['name']} {$contact['email']}");
                    } catch (\Exception $e) {
                        // do nothing as we don't want to fail on unsent mails
                    }
                }
            }
        }
        $this->logNotification('Mail out finished '.date('d-m-y h:i:s', time()));
    }

    public function notifyRedHotSubmission($jobId, $advertiserId)
    {
        $currentDate = new \DateTime('now');
        $this->jobId = $jobId;
        $submittedDate = $currentDate->format('d-m-Y H:i:s');
        $this->logNotification('Mail out started (notify red hot submission) '.date('d-m-y h:i:s', time()));
        $mail = $this->app->service('Mail');

        $advertiser = $this->getAdvertiser($advertiserId);
        $cadSupervisors = $this->notificationConfig['cadSupervisors'];

        $emailVariables = [
            'jobId' => $jobId,
            'advertiserName' => $advertiser['advertiserName'],
            'submittedDate' => $submittedDate
        ];

        $subjectLine = $this->getRedHotSubjectLine($jobId);

        foreach($cadSupervisors as $cadSupervisor) {
            if (!empty($cadSupervisor['email'])) {
                $emailVariables['name'] = $cadSupervisor['name'];
                $html = $mail->loadHtml('redHotSubmission.email', $emailVariables);
                try {
                    $mail->sendHtmlMessage(
                        [$cadSupervisor['email'] => $cadSupervisor['name']],
                        [$this->fromAddr => $this->fromName],
                        $subjectLine,
                        $html
                    );
                    $this->logNotification("Sent mail to {$cadSupervisor['name']} {$cadSupervisor['email']}");
                } catch (\Exception $e) {
                    // do nothing as we don't want to fail on unsent mails
                }
            }
        }
        $this->logNotification('Mail out finished '.date('d-m-y h:i:s', time()));
    }

    /**
     * Notify dynamic charge code submission
     *
     * @param $jobId
     * @param $advertiserId
     */
    public function notifyDynamicChargeCodeSubmission($jobId, $advertiserId)
    {
        $currentDate = new \DateTime('now');
        $this->jobId = $jobId;
        $submittedDate = $currentDate->format('d-m-Y H:i:s');
        $this->logNotification('Mail out started (notify dynamic charge code submission) '.date('d-m-y h:i:s', time()));
        $mail = $this->app->service('Mail');

        $advertiser = $this->getAdvertiser($advertiserId);
        $dynamicChargeCodesRecipients = $this->notificationConfig['dynamicChargeCodes'];

        $emailVariables = [
            'jobId' => $jobId,
            'advertiserName' => $advertiser['advertiserName'],
            'submittedDate' => $submittedDate
        ];

        $subjectLine = $this->getDynamicChargeCodeSubjectLine($jobId);

        foreach($dynamicChargeCodesRecipients as $aRecipient) {
            if (!empty($aRecipient['email'])) {
                $emailVariables['name'] = $aRecipient['name'];
                $html = $mail->loadHtml('dynamicChargeCodeSubmission.email', $emailVariables);
                try {
                    $mail->sendHtmlMessage(
                        [$aRecipient['email'] => $aRecipient['name']],
                        [$this->fromAddr => $this->fromName],
                        $subjectLine,
                        $html
                    );
                    $this->logNotification("Sent mail to {$aRecipient['name']} {$aRecipient['email']}");
                } catch (\Exception $e) {
                    // do nothing as we don't want to fail on unsent mails
                }
            }
        }
        $this->logNotification('Mail out finished '.date('d-m-y h:i:s', time()));
    }

    public function notifyAdditionalCharge($jobId, $attachmentPaths)
    {
        $this->jobId = $jobId;
        $job = $this->getJob($jobId);
        $advertiser = $this->getAdvertiser($job->getAdvertiserId());
        $this->logNotification('Mail out started (notify job additional charge) '.date('d-m-y h:i:s', time()));
        $mail = $this->app->service('Mail');
        $allContacts = $this->getAllJobContacts($jobId);
        foreach($allContacts as $contacts) {
            foreach ($contacts as $contact) {
                if (!empty($contact['email'])) {
                    $html = $mail->loadHtml('additionalCharge.email', ['jobId' => $jobId]);
                    try {
                        $mail->setAttachments(json_decode($attachmentPaths));
                        $mail->sendHtmlMessage([$contact['email'] => $contact['name']],
                            [$this->fromAddr => $this->fromName],
                            $this->getRedHotSubjectLine() . 'Additional Charges applied - Reference No : ' . $jobId . ', ' . $advertiser['advertiserName'],
                            $html );
                        $this->logNotification("Sent mail to {$contact['name']} {$contact['email']}");
                    } catch (\Exception $e) {
                        // do nothing as we don't want to fail on unsent mails
                    }
                }
            }
        }
    }

    public function notifyPaymentFailure($jobId)
    {
        $this->jobId = $jobId;
        $job = $this->getJob($jobId);
        $advertiser = $this->getAdvertiser($job->getAdvertiserId());
        $this->logNotification('Mail out started (notify payment failure) '.date('d-m-y h:i:s', time()));
        $mail = $this->app->service('Mail');
        $allContacts = $this->getAllJobContacts($jobId);
        foreach($allContacts as $contacts) {
            foreach ($contacts as $contact) {
                if (!empty($contact['email'])) {
                    $html = $mail->loadHtml('paymentFailure.email', ['jobId' => $jobId]);
                    try {
                        $mail->sendHtmlMessage([$contact['email'] => $contact['name']],
                            [$this->fromAddr => $this->fromName],
                            $this->getRedHotSubjectLine() ."Payment has failed for {$jobId} reference number, "  . $advertiser['advertiserName'],
                            $html );
                        $this->logNotification("Sent mail to {$contact['name']} {$contact['email']}");
                    } catch (\Exception $e) {
                        $this->logNotification("{$e->getMessage()}");
                        // do nothing as we don't want to fail on unsent mails
                    }
                }
            }
        }

    }

    public function notifyCadIssued($jobId, $attachmentPaths)
    {
        $this->jobId = $jobId;
        $job = $this->getJob($jobId);
        $advertiser = $this->getAdvertiser($job->getAdvertiserId());
        $this->logNotification('Mail out started (notify job status change) '.date('d-m-y h:i:s', time()));
        $mail = $this->app->service('Mail');
        $allContacts = $this->getAllJobContacts($jobId, true, true, true);

        // Default to sending the cad template with the following email subject
        $template = 'cadissued.email';
        $emailSubject = $this->getRedHotSubjectLine() ."CAD Number/s Issued - Reference {$jobId}, " . $advertiser['advertiserName'];

        // If it's a precheck, use the precheck template instead
        $jobModel = $this->app->model('job');
        $jobModel->setJobId($jobId);
        $jobModel->load();

        // Get list of CAD numbers
        $keyNumbers = $jobModel->getAllKeyNumbers();
        $cadNumbers = array();
        foreach($keyNumbers as $keyNumber) {
            $cadNumbers[] .= $keyNumber['cadNumber'];
        }
        $cadNumberString = implode('<br>',$cadNumbers);

        if($jobModel->isPrecheck() == true) {
            $template = 'TvcPrecheckProcessed.email';
            $emailSubject = $this->getRedHotSubjectLine() ."Precheck TVC/s processed - Reference {$jobId}, " . $advertiser['advertiserName'];
        }
        foreach($allContacts as $contacts) {
            foreach ($contacts as $contact) {
                if (!empty($contact['email'])) {
                    $html = $mail->loadHtml($template, ['jobId' => $jobId, 'cadNumberString' => $cadNumberString]);
                    try {
                        $mail->setAttachments(json_decode($attachmentPaths));
                        $mail->sendHtmlMessage([$contact['email'] => $contact['name']],
                            [$this->fromAddr => $this->fromName],
                            $emailSubject,
                            $html );
                        $this->logNotification("Sent mail to {$contact['name']} {$contact['email']}");
                    } catch (\Exception $e) {
                        $this->logNotification("{$e->getMessage()}");
                        // do nothing as we don't want to fail on unsent mails
                    }
                }
            }
        }

    }

    public function notifyOrderForm($jobId, $attachmentPaths)
    {
        $this->jobId = $jobId;
        $job = $this->getJob($jobId);
        $advertiser = $this->getAdvertiser($job->getAdvertiserId());
        $this->logNotification('Mail out started (final order form to be mailed out) '.date('d-m-y h:i:s', time()));
        $mail = $this->app->service('Mail');
        $allContacts = $this->getAllJobContacts($jobId);

        $jobModel = $this->app->model('job');
        $jobModel->setJobId($jobId);
        $jobModel->load();

        $flavourText = 'The Final Order Form for your Job with Reference number ' . $jobId . ' follows for your reference.';
        $precheck = '';

        if($jobModel->isPrecheck() == true) {
            $flavourText = 'The Final Order Form for pre-check with job reference number ' . $jobId . ' follows for your reference.';
            $precheck = " Precheck ";
        }

        foreach($allContacts as $contacts) {
            foreach ($contacts as $contact) {
                if (!empty($contact['email'])) {
                    $html = $mail->loadHtml('finalorderform.email', ['jobId' => $jobId, 'flavourText' => $flavourText]);
                    try {
                        $mail->setAttachments(json_decode($attachmentPaths));
                        $mail->sendHtmlMessage([$contact['email'] => $contact['name']],
                            [$this->fromAddr => $this->fromName],
                            $this->getRedHotSubjectLine() . $precheck . 'Final Order Form - Reference No : ' . $jobId . ', ' . $advertiser['advertiserName'],
                            $html );
                        $this->logNotification("Sent mail to {$contact['name']} {$contact['email']}");
                    } catch (\Exception $e) {
                        echo $e->getMessage();
                        // do nothing as we don't want to fail on unsent mails
                    }
                }
            }
        }

    }


    /**
     * @param $agencyId
     * @return array
     *
     * Get all agency contacts for a given agency in an array separated by primary, agency users, and additional
     *
     * additional activeOnly flag set to false if emails sent to non-active people
     *
     * N.b not used anymore
     */
    private function getAllAgencyContacts($agencyId, $activeOnly = true)
    {
        $allContacts = [];

        $agencyModel = $this->app->model('agency');
        $agencyModel->setAgencyId($agencyId);
        $agencyModel->load();
        /* get the primary contacts */
        $allContacts['primary'] = ($agencyModel->getPrimaryContactEmail() === null) ? [] : [
            'name' => $agencyModel->getPrimaryContactName(),
            'email' => $agencyModel->getPrimaryContactEmail()
        ];

        /* now get the linked agency users */
        $allContacts['agency'] = [];
        try {
            $contacts = $this->app->collection('agencyuserlist')->getAllAgencyusers($agencyId);

            foreach ($contacts as $contact) {
                if ( $activeOnly ) {
                    if ( $contact['isActive'] ) {
                        $allContacts['agency'][] = ['name' => $contact['firstName'], 'email' => $contact['email']];
                    }
                } else {
                    $allContacts['agency'][] = ['name' => $contact['firstName'], 'email' => $contact['email']];
                }
            }
        } catch (NotFoundException $e) {
            //dont do anything as we just skip any missing emails
        }


        /* now get the network uses for this agency */
        $allContacts['network'] = [];
        $networkId = $agencyModel->getNetworkId();
        if ($networkId !== null) {
            $networkUsers = $this->app->collection('NetworkUserlist');

            $networkUsers->setParams(['networkId' => $networkId]);
            $networkUsers->setOrder();

            $data = array();
            try {
                $networkUsers->fetch();
                $data = $networkUsers->list;
                foreach($data as $user) {
                    $allContacts['network'][] = ['name' => $user['firstName'], 'email' => $user['email']];
                }
            } catch(NotFoundException $e) {
                //dont do anything as we just skip any missing emails
            }

        }
        /* now get all the additional contacts */
        $allContacts['additional'] = [];
        $conditions = array('contactable_id' => $agencyId,
            'contactable_type' => 'App\Models\Agencies');
        $capsule = $this->app->service('eloquent')->getCapsule();
        $results = Contact::where($conditions)->get();

        foreach($results as $result) {
            $datum = $result->toArray();
            if ( $activeOnly ) {
                if ($datum['active']) {
                    $allContacts['additional'][] = ['name' => $datum['name'], 'email' => $datum['email']];
                }
            } else {
                $allContacts['additional'][] = ['name' => $datum['name'], 'email' => $datum['email']];
            }
        }

        return $allContacts;
    }

    /**
     * @param $jobId
     * @param bool $includeCADAdviceOnly
     * @param bool $activeOnly
     * @param bool $accountsContacts - Retrieve accounts contacts as well
     * @return array
     *
     * Get all the contacts for a job
     *
     * When $includeCADAdviceOnly = false, contacts with cad advice only are NOT returned.
     * When $includeCADAdviceOnly = true, ALL contacts are returned
     *
     */
    public function getAllJobContacts($jobId, $includeCADAdviceOnly = false, $activeOnly = true, $accountsContacts = false)
    {
        $allContacts = [];
        $job = $this->app->model('job');
        $job->setJobId($jobId);
        $job->load();
        $jobData = $job->getFullJob();

        $allContacts['cadSupervisors'] = $this->getCadSupervisors($jobId);

        $submittedUserId = $job->getCreatedBy();
        $agencyId = $job->getAgencyId();
        $agencyUser = $this->app->model('AgencyUser');
        /* get the agency user who created the job */
        try {
            $agencyUserData = $agencyUser->getAgencyUserDetailsBySysId($submittedUserId);
            $allContacts['agencyUser'][] = [
                'name' => $agencyUserData['firstName'],
                'email' => $agencyUserData['email']
            ];
        } catch (NotFoundException $e) {
            // don't do anything, they just don't have a contact attached
        }

        if ($accountsContacts == true) {
            $allContacts['accounts'][] = [
                'name' => $jobData['agency']['accountsContact'],
                'email' => $jobData['agency']['accountsEmailAddress'],
            ];
        }

        /* now get all additional AGENCY contacts */
        $allContacts['additionalAgency'] = $this->getAdditionalAgencyContacts($agencyId, $includeCADAdviceOnly, $activeOnly);

        /* now get all additional contacts linked to the specific JOB */
        $allContacts['additionalJob'] = $this->getAdditionalContacts($jobId, $includeCADAdviceOnly, $activeOnly);

        // TODO Primary and secondary contact notifications

        $allContacts['agencyContacts'] = $this->getPrimaryAndSecondaryContacts($jobData['agency']);

        return $allContacts;

    }

    public function getCadSupervisors($jobId)
    {

        $job = $this->app->model('job');
        $job->setJobId($jobId);
        $job->load();

        if ($job->isRedHotJob()) {
            return $this->notificationConfig['cadSupervisors'];
        }

        return array();

    }

    private function getPrimaryAndSecondaryContacts($agencyData)
    {
        $contacts = array();
        if(!empty($agencyData['primaryContactName']) && !empty($agencyData['primaryContactEmail'])) {
            $contacts[] = ['name' => $agencyData['primaryContactName'], 'email' => $agencyData['primaryContactEmail']];
        }
        if(!empty($agencyData['secondaryContactName']) && !empty($agencyData['secondaryContactEmail'])) {
            $contacts[] = ['name' => $agencyData['secondaryContactName'], 'email' => $agencyData['secondaryContactEmail']];
        }
        return $contacts;
    }

    private function getAdditionalContacts($jobId, $includeCADAdviceOnly, $activeOnly)
    {
        $this->app->service('eloquent')->getCapsule();
        $contacts = [];

        $conditions = array(
            'contactable_id' => $jobId,
            'contactable_type' => 'App\Models\Job',
        );

        if ($includeCADAdviceOnly === false) {
            $results = Contact::where($conditions)->where('notification_type', '!=', 2)->get();
        } else {
            $results = Contact::where($conditions)->get();
        }

        foreach ($results as $result) {
            $datum = $result->toArray();
            if ($activeOnly) {
                if ($datum['active']) {
                    $contacts[] = ['name' => $datum['name'], 'email' => $datum['email']];
                }
            } else {
                $contacts[] = ['name' => $datum['name'], 'email' => $datum['email']];
            }
        }

        return $contacts;
    }

    private function getAdditionalAgencyContacts($agencyId, $includeCADAdviceOnly, $activeOnly)
    {
        $this->app->service('eloquent')->getCapsule();
        $contacts = [];
        $conditions = array(
            'contactable_id' => $agencyId,
            'contactable_type' => 'App\Models\Agencies',
        );

        if ($includeCADAdviceOnly === false) {
            $results = Contact::where($conditions)->where('notification_type', '!=', 2)->get();
        } else {
            $results = Contact::where($conditions)->get();
        }


        foreach ($results as $result) {
            $datum = $result->toArray();
            if ($activeOnly) {
                if ($datum['active']) {
                    $contacts[] = ['name' => $datum['name'], 'email' => $datum['email']];
                }
            } else {
                $contacts[] = ['name' => $datum['name'], 'email' => $datum['email']];
            }
        }
        return $contacts;
    }

    /**
     * @param $message
     * @throws \Exception
     *
     * Logs any notification to the log file
     */
    public function logNotification($message) {
        if ($this->notificationConfig['logging'] !== true) {
            return;
        }

        if (!is_file($this->notificationConfig['logFile']) && $this->notificationConfig['logFile'] != "php://stdout" ) {
            throw new \Exception("Invalid or incorrect permissions for mail logging file");
        }

        file_put_contents($this->notificationConfig['logFile'], $message.PHP_EOL, FILE_APPEND);

    }


    /**
     * Gets the red hot subject line if the job id is set in the class
     * Job id is not set in the station reply function since red hot subject lines are only for agency users
     * @return string
     */
    public function getRedHotSubjectLine()
    {
        if (empty($this->jobId)) {
            return '';
        }

        $job = $this->app->model('job');
        $job->setJobId($this->jobId);
        $job->load();
        $jobData = $job->getFullJob();

        if (!$job->isRedHotJob()) {
            return '';
        }

        $advertiser = $this->getAdvertiser($jobData['advertiserId']);

        $subjectLine = "Red Hot - Ref #{$this->jobId} | Advertiser - {$advertiser['advertiserName']} | ";

        return $subjectLine;
    }

    /**
     * Gets the dynamic charge code subject line if the job id is set in the class
     * @return string
     */
    public function getDynamicChargeCodeSubjectLine()
    {
        if (empty($this->jobId)) {
            return '';
        }

        $job = $this->app->model('job');
        $job->setJobId($this->jobId);
        $job->load();
        $jobData = $job->getFullJob();

        $advertiser = $this->getAdvertiser($jobData['advertiserId']);

        $subjectLine = "Dynamic charge code - Ref #{$this->jobId} | Advertiser - {$advertiser['advertiserName']} | ";

        return $subjectLine;
    }

    /**
     * Retrieve the advertiser
     * @return Advertiser
     */
    public function getAdvertiser($advertiserId) {

        $advertiserMdl = $this->app->model('advertiser');
        $advertiser = $advertiserMdl->findByAdvertiserId($advertiserId);
        return $advertiser->getAsArray();
    }

    /**
     * @param $jobId
     * @return Job
     */
    public function getJob($jobId) {
        $job = $this->app->model('job');
        $job->setJobId($jobId);
        $job->load();
        return $job;
    }

    public function processEmail($template, $htmlVariables, $to, $from, $subject)
    {
        $html = $this->mail->loadHtml($template, $htmlVariables);

        $subject = $this->getRedHotSubjectLine() . $subject;

        try {
            $this->mail->sendHtmlMessage($to, $from,  $subject, $html );
            $this->logNotification("Sent mail to ".array_keys($to)[0]);
        } catch (\Exception $e) {
            $this->logNotification("ERROR: Mail not sent to ".array_keys($to)[0]);
            // do nothing as we don't want to fail on unsent mails
        }
    }

    /**
     * @return mixed
     */
    public function getFromAddr()
    {
        return $this->fromAddr;
    }

    /**
     * @param mixed $fromAddr
     */
    public function setFromAddr($fromAddr)
    {
        $this->fromAddr = $fromAddr;
    }


}