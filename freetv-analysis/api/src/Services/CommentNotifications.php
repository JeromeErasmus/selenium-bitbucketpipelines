<?php

namespace App\Services;
use App\Models\Comment;
use App\Models\TvcRequirement as Model;

/**
 * Created by PhpStorm.
 * User: thimira.gunasekera
 * Date: 2/02/2016
 * Time: 10:54 AM
 */
class CommentNotifications extends Notifications
{

    public function test()
    {
        $this->notifyAgencyReply($this->mailConfig['testCommentId'],1);
        $this->notifyAgencyReply($this->mailConfig['testCommentId'],2);
        $this->notifyStationReply($this->mailConfig['testStationCommentId'],1);
        $this->notifyRequirementComment($this->mailConfig['testRequirementCommentId'],1);
        $this->notifyRequirementComment($this->mailConfig['testRequirementCommentId'],2);
    }
    public function notifyAgencyReply($commentId, $replyType)
    {
        $replyComment = $this->getCommentData($commentId);
        $parentComment = $this->getCommentData($replyComment['parent']);
        $this->jobId = $replyComment['ref_id'];

        $repliedBy = $replyComment['created_by']['userName'];

        if(empty($parentComment['created_by'])) {
            $this->logNotification("Error in agency reply. Failed to locate parent comment user details. ");
            exit;
        }

        $agencyUser = $parentComment['created_by'];
        $allContacts = [];

        if ( $replyType == '1' && !empty($agencyUser['email']) && !empty($agencyUser['firstName'])) {       //reply parent only
            $allContacts['parentContact'] = [
                [
                    'name' => $agencyUser['firstName'],
                    'email' => $agencyUser['email']
                ]
            ];
            $template = 'agencycommentreply.email';
            $allContacts['cadSupervisors'] = $this->getCadSupervisors($replyComment['ref_id']);
        } else if ( $replyType == '2') {
            $template = 'agencycommentreplyall.email';
            $allContacts = $this->getAllJobContacts($replyComment['ref_id']);
        }
        $parentInAllContacts = false;       // if the person who made the intiial comment is already part of all contacts, we don't want to send the email twice to them
        foreach($allContacts as $contacts) {
            foreach ($contacts as $contact) {
                if (empty($contact['email'])) {
                    continue;
                }

                if ($contact['email'] === $agencyUser['email']) {
                    $parentInAllContacts = true;
                }

                $htmlVariables = [
                    'firstname' => !empty($contact['name']) ? $contact['name'] : '',
                    'jobId' => $replyComment['ref_id'],
                    'repliedBy' => $repliedBy,
                    'originalComment' => $parentComment['comment'],
                    'replyComment' => $replyComment['comment'],
                    'parentCommentBy' => $agencyUser['firstName']." ".$agencyUser['lastName']
                ];

                $job = $this->getJob($htmlVariables['jobId']);
                $advertiser = $this->getAdvertiser($job->getAdvertiserId());

                $this->processEmail(
                    'agencycommentreplyall.email',
                    $htmlVariables,
                    [$contact['email'] => $contact['name']],
                    [$this->getFromAddr() => $this->fromName],
                    "Job Updated - Reference No: {$htmlVariables['jobId']}, " . $advertiser['advertiserName']
                );

            }
        }

        if ($parentInAllContacts === false) {

            $htmlVariables = [
                'firstname' => $agencyUser['firstName'],
                'jobId' => $replyComment['ref_id'],
                'repliedBy' => $repliedBy,
                'originalComment' => $parentComment['comment'],
                'replyComment' => $replyComment['comment']
            ];

            $job = $this->getJob($htmlVariables['jobId']);
            $advertiser = $this->getAdvertiser($job->getAdvertiserId());

            $this->processEmail($template, $htmlVariables, [$agencyUser['email'] => $agencyUser['firstName']],
                [$this->getFromAddr() => $this->fromName],  "Job Updated - Reference No: {$htmlVariables['jobId']}, " . $advertiser['advertiserName']);

        }

    }

    public function notifyStationReply($commentId, $replyType)
    {
        $replyComment = $this->getCommentData($commentId);
        $parentComment = $this->getCommentData($replyComment['parent']);

        if (!empty($parentComment['created_by']['email']) && !empty($parentComment['created_by']['firstName'])) {
            $contacts = [
                [
                    'name' => $parentComment['created_by']['firstName'],
                    'email' => $parentComment['created_by']['email'],
                ]
            ];

            $cadSupervisors = $this->getCadSupervisors($replyComment['ref_id']);

            $contacts = array_merge($contacts,$cadSupervisors);

            foreach ($contacts as $contact) {

                $htmlVariables = [
                    'firstname' => !empty($contact['name']) ? $contact['name'] : '',
                    'jobId' => $replyComment['ref_id'],
                    'repliedBy' => $replyComment['created_by']['userName'],
                    'originalComment' => $parentComment['comment'],
                    'replyComment' => $replyComment['comment']
                ];

                $job = $this->getJob($htmlVariables['jobId']);
                $advertiser = $this->getAdvertiser($job->getAdvertiserId());

                $this->processEmail(
                    'agencycommentreply.email',
                    $htmlVariables,
                    [$contact['email'] => $contact['name']],
                    [$this->getFromAddr() => $this->fromName],
                    "Job Updated - Reference No: {$htmlVariables['jobId']}, " . $advertiser['advertiserName']
                );

            }

        }
    }

    /**
     * @param $commentId
     * @param $replyType
     */
    public function notifyRequirementComment($commentId,$replyType)
    {
        $replyComment = $this->getCommentData($commentId);
        $refId = $replyComment['ref_id'];
        $capsule = $this->app->service('eloquent')->getCapsule();
        $model = Model::findOrFail($refId);
        $requirementArray = $model->getAsArray();
        $jobId = $requirementArray['jobId'];
        $this->jobId = $jobId;
        $parentComment = $this->getCommentData($replyComment['parent']);

        $repliedBy = $replyComment['created_by']['userName'];
        $allContacts = [];

        if(empty($parentComment['created_by'])) {
            $this->logNotification("Error in agency reply. Failed to locate parent comment user details. ");
            exit;
        }
        $agencyUser = $parentComment['created_by'];

        if ( $replyType == '1' && !empty($agencyUser['email']) && !empty($agencyUser['firstName'])) {       //reply parent only

            $allContacts['parentContact'] = [
                [
                    'name' => $agencyUser['firstName'],
                    'email' => $agencyUser['email']
                ]
            ];

            $allContacts['cadSupervisors'] = $this->getCadSupervisors($jobId);

        } else if ( $replyType == '2') {
            $allContacts = $this->getAllJobContacts($jobId);
        }

        $parentInAllContacts = false;       // if the person who made the intiial comment is already part of all contacts, we don't want to send the email twice to them

        foreach($allContacts as $contacts) {
            foreach ($contacts as $contact) {
                if (empty($contact['email'])) {
                    continue;
                }

                if ($contact['email'] === $agencyUser['email']) {
                    $parentInAllContacts = true;
                }

                $htmlVariables = [
                    'firstname' => !empty($contact['name']) ? $contact['name'] : '',
                    'jobId' => $jobId,
                    'repliedBy' => $repliedBy,
                    'originalComment' => $parentComment['comment'],
                    'replyComment' => $replyComment['comment'],
                    'parentCommentBy' => $agencyUser['firstName']." ".$agencyUser['lastName']
                ];

                $job = $this->getJob($htmlVariables['jobId']);
                $advertiser = $this->getAdvertiser($job->getAdvertiserId());

                $this->processEmail(
                    'agencycommentreplyall.email',
                    $htmlVariables,
                    [$contact['email'] => $contact['name']],
                    [$this->getFromAddr() => $this->fromName],
                    "Job Updated - Reference No: {$htmlVariables['jobId']}, " . $advertiser['advertiserName']
                );

            }
        }
        if ($parentInAllContacts === false) {

            $htmlVariables = [
                'firstname' => $agencyUser['firstName'],
                'jobId' => $jobId,
                'repliedBy' => $repliedBy,
                'originalComment' => $parentComment['comment'],
                'replyComment' => $replyComment['comment']
            ];

            $this->processEmail('agencycommentreply.email', $htmlVariables, [$agencyUser['email'] => $agencyUser['firstName']],
                [$this->getFromAddr() => $this->fromName],  "New Comment - Reference {$htmlVariables['jobId']}");

        }
    }

    private function getCommentData($commentId)
    {
        $data = Comment::with('commentType')->where('id', $commentId)->get();
        if(!isset($data[0]) ) {
            throw new \Exception("Invalid id specified");
        }
        $commentData = $data[0]->toArray();

        if (!empty($commentData['parent'])) {       // this is a reply, so nest CAD user
            try {
                $user = $this->app->model('User');
                $user->setUserSysid($commentData['created_by']);
                $user->load();
                $commentData['created_by'] = $user->getAsArray();
                unset($commentData['created_by']['userPermissionSet']);
                return $commentData;
            } catch (NotFoundException $e) {
                $commentData['created_by'] = null;
                // cannot find user info
            }

        }
        $commentTypeId = !empty($commentData['commentType']['id']) ? $commentData['commentType']['id'] : null;

        if ($commentTypeId == 1 || $commentTypeId == 3) {      //nest in agency user
            try {
                $agencyUser = $this->app->model('agencyUser');
                $commentData['created_by'] = $agencyUser->getAgencyUserDetailsBySysId($commentData['created_by']);
            } catch(NotFoundException $e) {
                $commentData['created_by'] = null;
                // cannot find agency user
            }
        } else if ($commentTypeId == 2 ) {   //nest in network user
            try {
                $networkUser = $this->app->model('networkUser');
                $commentData['created_by'] = $networkUser->findBySysId($commentData['created_by'])->getAsArray();
            } catch(NotFoundException $e) {
                $commentData['created_by'] = null;
                // cannot find netowkr user
            }
        }
//        else if ($commentTypeId == 3) {       // no idea yet - requirement comments
//
//        } -- removed block check because requirement comments should function like agency comments

        return $commentData;
    }

}