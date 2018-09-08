<?php
/**
 * Created by PhpStorm.
 * User: jeremy.paul
 * Date: 8/10/2015
 * Time: 1:50 PM
 */

namespace App\Models;

use App\Models\EloquentModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class StopCreditReasons extends EloquentModel
{
    use SoftDeletes;

    protected $primaryKey = 'scr_id';
    protected $table = 'stop_credits';
    public $timestamps = false;

    /**
     * For soft delete functionality
     */

    protected $dates = ['deleted_at'];

    protected $fieldMap = [
        'id' => [
            'name' => 'scr_id',
        ],
        'stopCreditReason' => [
            'name' => 'scr_reason',
            'rules' => 'max:100|required',
        ],
        'stopCreditNumber' => [
            'name' => 'scr_number',
            'rules' => 'max:50|required',
        ]
    ];
}