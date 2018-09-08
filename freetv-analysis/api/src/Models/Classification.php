<?php

namespace App\Models;

use App\Models\EloquentModel;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Description of Contact
 *
 * @author Jeremy
 */
class Classification extends EloquentModel
{

    /**
     * override the default primary key
     */
    protected $primaryKey = 'cla_id';
    protected $table = 'classifications';
    public $timestamps = false;
    
    /**
     * this is required for soft deleting
     * @var type 
     */
    
    protected $fieldMap = [
        'id' => [
            'name' => 'cla_id',
        ],
        'claCode' => [
            'name' => 'cla_code',
        ],
        'claDescription' => [
            'name' => 'cla_description',
        ],
        'claActive' => [
            'name' => 'cla_active',
        ],
    ];

}
