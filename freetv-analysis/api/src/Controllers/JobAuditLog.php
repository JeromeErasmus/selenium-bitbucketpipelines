<?php
/**
 * Created by PhpStorm.
 * User: jeremy.paul
 * Date: 8/01/2016
 * Time: 10:53 AM
 */

namespace App\Controllers;

use Elf\Http\Request;
use Elf\Event\RestEvent;
use Elf\Exception\NotFoundException;
use App\Models\JobAuditLog as Model;
use App\Models\EloquentModel;

class JobAuditLog extends RestEvent{
    private $entity ="jobAuditLog";

    public function handleGet(Request $request)
    {
        $id = $request->query('id');
        $jobId = $request->query('jobId');
        $top = $request->query('top');
        
        $capsule = $this->app->service('eloquent')->getCapsule();

        $jobAuditLog = new Model();

        if(null === $id && null === $jobId)
        {
            throw new NotFoundException('Please enter an ID or a Job ID');
        }

        if ($id) {
            $result = Model::with('auditLogs')->where('id','=',$id)->get();
            $jobLog =  EloquentModel::arrayToRestful($result);
            // The results are an array within an array, hence referencing the [0] indexed array
            $jobLog[0]['auditLogs']['createdBy'] =  $this->app->service('User')->retrieveUserDetails($jobLog[0]['auditLogs']['createdBy']);
            unset($jobLog[0]['auditLogs']['createdBy']['userPermissionSet']);
            return $jobLog;
        }

        $results = $jobAuditLog->belongingToAJob($jobId, $top)->get();

        $jobLogs = EloquentModel::arrayToRestful($results);
        foreach($jobLogs as $index => $jobLog) {
            $jobLogs[$index]['auditLogs']['createdBy'] = $this->app->service('User')->retrieveUserDetails($jobLog['auditLogs']['createdBy']);;
            unset($jobLogs[$index]['auditLogs']['createdBy']['userPermissionSet']);

            if(!empty($jobLog['auditLogs']['additionalInformation'])) {
                $jobLogs[$index]['auditLogs']['additionalInformation'] = json_decode($jobLog['auditLogs']['additionalInformation']);
            }
        }
	
        return $jobLogs;
    }
}