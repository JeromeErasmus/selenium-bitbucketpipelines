<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Controllers;


use App\Models\Contact;
use App\Services\EmailInterface;
use Elf\Exception\NotFoundException;
use Elf\Exception\MalformedException;
use Elf\Http\Request;
use Elf\Event\RestEvent;
use App\Models\MyMetaModel;
use App\Models\JobDeclaration;

/**
 * Description of Job
 *
 * @author michael
 */
class Job extends AppRestController
{

    public function handleDelete(Request $request){
        $id = $request->query('id');
        if(null === $id || !is_numeric($id)){
            throw new MalformedException("Provided job reference can not be processed");
        }

        $data = $request->retrieveJSONInput();

        $jobMdl = $this->app->model('Job');
        $jobMdl->setJobId($id);
        $jobData = $jobMdl->load();

        //can't delete submitted jobs || //make sure the job exists and is not deleted
        if(!empty($jobData['submittedBy']) || null !== $jobMdl->getDeletedAt() || empty($data)){
            $this->set('status_code', 400);//Bad Request? Because we should be idempotent and not allow a delete command to run more than once
            return false;
        }

        $delete = $jobMdl->deleteJobAndAssocRecords($data);

        if($delete){
            //this is the first time its run
            //Accepted, probably, because we're soft deleting?
            $this->set('status_code', 202);
        }
        else{
            $this->set('status_code', 400);
        }
        return;

    }

    /**
     *
     * @param Request $request
     * @return type
     */
    public function handleGet(Request $request)
    {
        $id = $request->query('id');
        if(null === $id) {
            return $this->getCollection($request);
        }

        $job = $this->app->model('job');

        $deleted = $request->query('deleted');
        if( !empty($deleted) && $request->query('deleted') == 1){
            $job->setShowDeleted($deleted);
        }

        $job->setJobId($id);
        $job->load();
        return $job->getFullJob();

    }

    /**
     *
     * @param Request $request
     * @return type
     */
    public function getCollection(Request $request)
    {
        $jobList = $this->app->collection('joblist');

        $restrictions = '';
        if($request->query('restrict') !== null) {
            $restrictions = explode('|',urldecode($request->query('restrict')));
        }

        $searchQuery = $request->query('q');

        $filters = $request->query();

        if ($searchQuery !== null) {
            $jobList->searchJobList($searchQuery, $restrictions, $filters );
        } elseif(!empty($restrictions)) {
            $data = array();
            $jobList->retrieveJobsWithRestrictions($restrictions, $filters);
        } else {
            //multiple selection
            if(!empty($filters['filterIds'])){

                return $this->applyMultiSelect($jobList, $filters);

            }else{
                $clientId = $request->query('clientId');

                $jobList->setClientId($clientId);
                $jobList->setParams($filters);
                $jobList->fetch();
            }
        }

        $data = $jobList->list;

        return $data;
    }

    /**
     *
     * @param Request $request
     */
    public function handlePost(Request $request)
    {
        $jobModel = $this->app->Model('Job');
        $data = $request->retrieveJSONInput();
        $data['createdBy'] = $this->app->service('user')->getCurrentUser()->getUserSysid();

        $jobModel->setFromArray($data);

        $jobModel->save();

        $clientId = $request->query('clientId');
        $url = "/job/clientId/$clientId/id/{$jobModel->getJobId()}";
        $this->set('locationUrl', $url);
        $this->set('status_code', 201);

    }

    public function handlePatch(Request $request)
    {
        $id = $request->query('id');
        $action = $request->query('action');

        if ($id === null) {
            $this->set('status_code', 400);         //bad request
            return ['error_msg' => 'No ID specified'];
        }

        if (strcasecmp($action, 'submit') != 0) {
            $inputData = $request->retrieveJSONInput();
            //checking for content type twice
            // we don't want any user to be able to edit the following fields
            unset($inputData['referenceNo']);
            unset($inputData['jobId']);
            unset($inputData['submissionDate']);
            unset($inputData['actionByDate']);
        }

        $jobMdl = $this->app->model('Job');
        $jobMdl->setJobId($id);
        $jobMdl->load();

        if ($action == 'submit') {
            if (!$jobMdl->validate($jobMdl->getAsArray())) {
                $this->set('status_code', 400);
                return $jobMdl->getErrors();
            }
            $this->validateJobSubmission($id);
            // If the job has made it past validation, mark it as submitted
            $jobMdl->submitJob($id);


            $redHotStatus = $jobMdl->getRedHotJob();

            if (!empty($redHotStatus)) {
                $this->sendRedHotNotification($jobMdl);
            } else {
                // Because late fees are only applicable to non-red hot jobs
                $jobMdl->setKeyNumberPriorityProcessingFee();
            }

            // Send dynamic charge code notification
            if ( $this->shouldSendDynamicChargeCodeNotification($jobMdl) ) {
                $this->sendDynamicChargeCodeNotification($jobMdl);
            }

            $jobMdl->submitInitialOrderForm($id);
            $this->set('status_code', 204);
            return;
        }

        $inputData['lastAmendedBy'] = $this->app->service('user')->getCurrentUser()->getUserSysid();
        $inputData['lastAmendDate'] = true;

        $jobMdl->setFromArray($inputData,true);
        $jobMdl->save();

        /* Runs the notifications asynchronously below */
        $jobTypeStatuses = $this->app->config->get('jobStatuses');
        if ( isset($inputData['jobStatusId']) && ( $inputData['jobStatusId'] == $jobTypeStatuses['agencyFeedback'] ) ) {
            $scriptExecutable = "php " . ASYNC_SCRIPT. " AsyncNotificationSendout "
                ."--ENVIRONMENT=".getenv("ENVIRONMENT") . " "
                ."--notificationType=awaitingfeedback "
                . "--agencyId=".$jobMdl->getAgencyId(). " --jobId=".$jobMdl->getJobId();

            $this->requestAsync($scriptExecutable);
        }

        $this->set('status_code', 204);

    }

    private function sendRedHotNotification($jobModel)
    {
        $job = $jobModel->getFullJob();
        $scriptExecutable = "php " . ASYNC_SCRIPT . " AsyncNotificationSendout "
            . "--ENVIRONMENT=".getenv("ENVIRONMENT") . " "
            . " --notificationType=redHotJobSubmitted"
            . " --jobId=" . $job['jobId']
            . " --advertiserId=" . $job['advertiserId'];
        $this->requestAsync($scriptExecutable);
    }

    /**
     * Send dynamic charge code notification
     *
     * @param $jobModel
     */
    private function sendDynamicChargeCodeNotification($jobModel)
    {
        $job = $jobModel->getFullJob();
        $scriptExecutable = "php " . ASYNC_SCRIPT . " AsyncNotificationSendout "
            . "--ENVIRONMENT=".getenv("ENVIRONMENT") . " "
            . " --notificationType=dynamicChargeCodeJobSubmitted"
            . " --jobId=" . $job['jobId']
            . " --advertiserId=" . $job['advertiserId'];
        $this->requestAsync($scriptExecutable);
    }

    /**
     * Check if we should send notification for dynamic charge codes
     *
     * @param $jobModel
     */
    private function shouldSendDynamicChargeCodeNotification($jobModel)
    {
        return $jobModel->validateJobHasDynamicChargeCodes();
    }

    private function validateJobSubmission($id)
    {
        $jobMdl = $this->app->model('Job');

        // Check that job has a declaration already

        $capsule = $this->app->service('eloquent')->getCapsule();
        $jobDeclarationModel = new JobDeclaration();
        $jobDeclarationModel->findOrFail($id);

        // Check that job has a script already

        $jobMdl->validateJobHasAScript($id);

        // Confirm that job data is valid

        $jobMdl->validateJobDataForSubmission($id);

        // Confirm that at least 1 Key Number exists

        $jobMdl->validateJobHasKeyNumbers($id);

        $jobMdl->validateJobKeyNumbersHaveValidChargeCodes($id);
        // Advertiser and agency are valid

        $jobMdl->validateAdvertiserAndAgency($id);

    }

    private function applyMultiSelect($jobList, $filters){
        $filterIdsArray = explode(',',$filters['filterIds']);
        $dataArray = array();
        foreach ($filterIdsArray as $filterId) {
            $params = array("filterId"=>$filterId);
            if($filterId == 6 && !empty($filters['assigned_to'])){
                $params['assigned_to'] = $filters['assigned_to'];
            }
            $jobList->setParams($params);
            $jobList->fetch();
            $dataArray[] = $jobList->list;
        }

        $result = array();
        foreach ($dataArray as $child){
            $result = array_unique(array_merge($result, $child), SORT_REGULAR);
        }
        $sorted = $this->array_orderby($result, 'actionByDate', SORT_ASC, 'submissionDate', SORT_ASC);
        return $sorted;
    }


    function array_orderby(){
        $args = func_get_args();
        $data = array_shift($args);
        foreach ($args as $n => $field) {
            if (is_string($field)) {
                $tmp = array();
                foreach ($data as $key => $row)
                    $tmp[$key] = $row[$field];
                $args[$n] = $tmp;
            }
        }
        $args[] = &$data;
        call_user_func_array('array_multisort', $args);
        return array_pop($args);
    }
}
