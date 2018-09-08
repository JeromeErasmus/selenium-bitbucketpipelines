<?php

namespace App\Models;

use App\Models\EloquentModel;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Description of Agency Groups
 *
 * @author shirleen
 */
class AgencyGroup extends EloquentModel
{

    use SoftDeletes;

    /**
     * override the default primary key
     */
    protected $primaryKey = "agr_id";
    protected $table = 'agency_groups';
    public $timestamps = false;

    /**
     * this is required for soft deleting
     * @var type
     */
    protected $dates = ['deleted_at'];

    protected $fieldMap = [
        'id' => [
            'name' => 'agr_id',
        ],
        'name' => [
            'name' => 'agr_name',
            'rules' => 'max:50|required',
        ],
    ];

}