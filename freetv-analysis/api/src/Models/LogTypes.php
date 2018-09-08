<?php
/**
 * Created by PhpStorm.
 * User: jeremy.paul
 * Date: 16/12/2015
 * Time: 4:59 PM
 */

namespace App\Models;


class LogTypes extends EloquentModel{
    protected $primaryKey = 'id';
    protected $table = 'audit_log_assoc_types';
    public $timestamps = false;

    protected $fieldMap = [
        'id' => [
            'name' => 'id',
        ],
        'displayName' => [
            'name' => 'display_name',
            'rules' => 'required'
        ],
        'routeName' => [
            'name' => 'route_name',
            'rule' => 'required'
        ],
    ];
}