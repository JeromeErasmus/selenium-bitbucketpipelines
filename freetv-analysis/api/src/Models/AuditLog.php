<?php
/**
 * Created by PhpStorm.
 * User: jeremy.paul
 * Date: 16/12/2015
 * Time: 1:37 PM
 */

namespace App\Models;

class AuditLog extends EloquentModel {
    protected $primaryKey = "id";
    protected $table = "audit_logs";

    public $timestamps = false;

    protected $fieldMap = [
        'id' => [
            'name' => 'id',
        ],
        'assocId' => [
            'name' => 'assoc_id',
            'rules' => 'required',
        ],
        'assocType' => [
            'name' => 'assoc_type',
            'rules' => 'required',
        ],
        'actionType' => [
            'name' => 'action_type',
            'rules' => 'required',
        ],
        'request' => [
            'name' => 'request',
        ],
        'createdBy' => [
            'name' => 'created_by',
            'rules' => 'required',
        ],
        'dateAndTime' => [
            'name' => 'date_and_time',
            'rules' => 'required',
        ],
        'additionalInformation' => [
            'name' => 'additional_information',
        ],
        'createdAt' => [
            'name' => 'created_at',
        ],
    ];
	
	public function getDates()
	{
		return['createdAt'];
	}
	
	
}