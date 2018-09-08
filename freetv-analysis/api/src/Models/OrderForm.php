<?php

namespace App\Models;

use App\Models\EloquentModel;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Description of TvcFormats
 *
 * This controller and model pair save data from the initial order form from the OAS and
 * the final order form confirmation from the job screen
 * @author Jeremy
 */
class OrderForm extends EloquentModel
{

    /**
     * override the default primary key
     */
    protected $primaryKey = "id";
    protected $table = 'order_forms';

    /**
     * this is required for soft deleting
     * @var type
     */

    protected $fieldMap = [
        'id' => [
            'name' => 'id',
        ],
//        Type is either initial order form, or final order form (1 or 2)
        'type' => [
            'name' => 'type',
        ],
        'jobId' => [
            'name' => 'job_id',
        ],
//        Eloquent dates were not working as expected, so I set the order form default time in sql to be the current time
        'createdAt' => [
            'name' => 'created_at',
        ],
        'createdBy' => [
            'name' => 'created_by',
        ],
        'inputData' => [
            'name' => 'input_data',
        ],
    ];
}