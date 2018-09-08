<?php
/**
 * Created by PhpStorm.
 * User: jeremy.paul
 * Date: 8/01/2016
 * Time: 10:14 AM
 */

namespace App\Models;


class JobAuditLog extends AuditLog{
    protected $primaryKey = "id";
    protected $table = "job_audit_log";

    public $timestamps = false;

    protected $fieldMap = [
        'id' => [
            'name' => 'id',
        ],
        'auditLogId' => [
            'name' => 'audit_log_id',
            'rules' => 'required',
        ],
        'jobId' => [
            'name' => 'job_id',
            'rules' => 'required',
        ],
    ];

    public function auditLogs()
    {
        return $this->hasOne('App\\Models\\AuditLog','id','audit_log_id');
    }
    
    /**
     * Gset audit logs by job id, return top <n> results if $top is set
     * @param type $query
     * @param type $jobId
     * @param type $top number of results to be returned
     * @return type
     */
    public function scopeBelongingToAJob($query, $jobId, $top = null)
    {
        $result = $query->with('auditLogs')->where('job_id',$jobId)->orderBy('id','desc');
        if(!empty($top)){
            return $result->take($top);
        }
        return $result;
    }
}