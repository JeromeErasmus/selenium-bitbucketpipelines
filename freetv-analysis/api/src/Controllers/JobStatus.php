<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Controllers;

use Elf\Exception\ConflictException;
use Elf\Http\Request;
use Elf\Event\RestEvent;
use App\Models\JobAlert;

/**
 * Description of Role
 *
 * @author michael
 */
class JobStatus extends RestEvent 
{

    const DEFAULT_ALERT_USER = 33;
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
        
        throw new \Exception('Not Implemented');
    }

    public function handlePatch(Request $request)
    {
        $requestArray = $request->retrieveJSONInput();
        if( !isset( $requestArray[ 'jobId' ] ) || !isset( $requestArray[ 'agencyUserId' ] ) ) {
            $this->set( 'status_code', 400 );
            return ( array( 'code' => 400, 'The request could not be understood by the server due to malformed syntax' ) );
        }
        //get the job id
        $jobId = $requestArray[ 'jobId' ];
        //get the agency user id
        $userId = $requestArray[ 'agencyUserId' ];
        //check if the user can modify this current job
        $job = $this->app->model( 'Job' );
        //this is either false or array('jobId'=>$jobId);
        $canModify = $job->canUserModify( $jobId, $userId );
        //change the status to ready for review
        if ( empty( $canModify ) ) {
            return false;
        }
        else {
            // Set the job to be follow up required
            $jobStatusUpdated = $job->updateJobStatus($jobId, 2);
            if($jobStatusUpdated === false){
                return false;
            }
        }
        //send a notification to the assigned and currently with CAD users
        // use canModify['job_owner']
        // use canModify['job_assigned_user']
        $alertTemplate = array(
            'alertMessage' => 'A job you are attached to has been resubmitted for review',
            'alertDestinationUserId' => '',
            'alertSourceUserId' => $userId,
            'jobId' => $jobId
        );
        $capsule = $this->app->service('eloquent')->getCapsule();
        $alertsToSend = array();

        $jobOwner = empty($canModify['job_owner']) ? self::DEFAULT_ALERT_USER : $canModify['job_owner'];
        $jobAssignedUser = empty($canModify['job_assigned_user']) ? self::DEFAULT_ALERT_USER : $canModify['job_assigned_user'];

        $alertToOwner = $alertTemplate;
        $alertToOwner['alertDestinationUserId'] = $jobOwner;
        $alertsToSend[] = $alertToOwner;

        // If the job owner and assigned user ids are different, queue the assigned user up to get an alert as well
        if ( $jobOwner != $jobAssignedUser) {
            $alertToAssignee = $alertTemplate;
            $alertToAssignee['alertDestinationUserId'] = $jobAssignedUser;
            $alertsToSend[] = $alertToAssignee;
        }

        if (empty ($alertsToSend)) {
            return;
        }

        foreach($alertsToSend as $alerts) {
            $jobAlert = new JobAlert();
            $jobAlert->setFromArray($alerts);
            $jobAlert->save();
        }

        return true;

    }

    /**
     * get the collection
     * @param type $request
     * @return type
     */
    public function getCollection($request)
    {
        $collection = $this->app->collection('jobStatusList');
        $collection->setParams($request->query());
        return $collection->getAll();
    }
    
    
    
}
