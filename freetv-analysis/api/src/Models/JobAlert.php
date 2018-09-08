<?php
/**
 * Created by PhpStorm.
 * User: jeremy.paul
 * Date: 14/01/2016
 * Time: 3:43 PM
 */

namespace App\Models;
/**
 * Class JobAlert
 * @package App\Models
 * [alertMessage] => The message to be conveyed through the alert (i.e. "Check this job out")
 * [alertDestinationUserId] => The user id for the user the alert is supposed to go to
 * [alertReadStatus] => Whether the alert has been read or not
 * [alertJobId] => The job id associated with the alert
 */

class JobAlert extends EloquentModel{
    protected $primaryKey = 'id';
    protected $table = 'job_alerts';

    /**
     * this is required for soft deleting
     * @var type
     */
    public $timestamps = false;

    protected $fieldMap = [
        'id' => [
            'name' => 'id',
        ],
        'alertMessage' => [
            'name' => 'alert_message',
            'rules' => 'required',
        ],
        'alertDestinationUserId' => [
            'name' => 'alert_destination_user_id',
            'rules' => 'required',
        ],
        'alertSourceUserId' => [
            'name' => 'alert_source_user_id',
            'rules' => 'required',
        ],
        'alertReadStatus' => [
            'name' => 'alert_read_status',
        ],
        'jobId' => [
            'name' => 'alert_job_id',
            'rules' => 'required',
        ],
    ];

}