<?php

namespace App\Models;

use App\Models\EloquentModel;
use Elf\Utility\Convert;

/**
 * Description of Agency Groups
 *
 * @author shirleen
 */
class TvcRequirement extends EloquentModel
{

    /**
     * override the default primary key
     */
    protected $primaryKey = 'id';
    protected $table = 'requirements_tvc';
    public $timestamps = false;

    protected $fieldMap = [
        'id' => [
            'name' => 'id',
        ],
        'reqId' => [
            'name' => 'rtv_req_id',
            'rules' => 'required'
        ],
        'tvcId' => [
            'name' => 'rtv_tvc_id',
            'rule' => 'required'
        ],
        'jobId' => [
            'name' => 'rtv_job_id',
            'rule' => 'required'
        ],
        'referenceNo' => [
            'name' => 'rtv_reference_no',
            'rule' => 'required'
        ],
        'keyNo' => [
            'name' => 'rtv_key_no',
        ],
        'cadVisible' => [
            'name' => 'rtv_visible_on_advice_slip',
            'type' => 'boolean'
        ],
        'activityReportVisible' => [
            'name' => 'rtv_visible_on_daily_activity',
            'type' => 'boolean'
        ],
        'mandatory' => [
            'name' => 'rtv_mandatory',
            'type' => 'boolean'
        ],
        'satisfied' => [
            'name' => 'rtv_satisfied',
            'type' => 'boolean'
        ],
    ];

    public function listRequirements()
    {
        return $this->hasMany('App\\Models\\Requirement','req_id', 'rtv_req_id');
    }
}