<?php

namespace App\Models;

use App\Models\EloquentModel;

/**
 * Description of Agency Groups
 *
 * @author shirleen
 */
class Requirement extends EloquentModel
{

    /**
     * override the default primary key
     */
    protected $primaryKey = 'req_id';
    protected $table = 'requirements';
    public $timestamps = false;

    protected $fieldMap = [
        'reqId' => [
            'name' => 'req_id',
        ],
        'jobId' => [
            'name' => 'req_job_id',
            'rules' => 'required'
        ],
        'referenceNo' => [
            'name' => 'req_reference_no',
            'rules' => 'required'
        ],
        'shortDescription' => [
            'name' => 'req_short_description'
        ],
        'category' => [
            'name' => 'req_category',
        ],
        'agencyNotes' => [
            'name' => 'req_agency_notes',
        ],
        'stationNotes' => [
            'name' => 'req_station_notes',
        ],
        'internalNotes' => [
            'name' => 'req_internal_notes',
        ],
        'createdAt' => [
            'name' => 'req_create_date',
        ],
        'updatedAt' => [
            'name' => 'req_modify_date',
        ],
        'createdBy' => [
            'name' => 'req_create_user_id',
        ],
        'modifiedBy' => [
            'name' => 'req_modify_user_id',
        ],
    ];


}