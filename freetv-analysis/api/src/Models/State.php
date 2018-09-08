<?php

namespace App\Models;

use App\Models\EloquentModel;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Description of TvcFormats
 *
 * @author adam
 */
class State extends EloquentModel
{
    
    use SoftDeletes;

    /**
     * override the default primary key
     */
    protected $primaryKey = "sta_id";
    protected $table = 'states';
    public $timestamps = false;
    
    /**
     * this is required for soft deleting
     * @var type 
     */
    protected $dates = ['deleted_at'];

    protected $fieldMap = [
        'id' => [
            'name' => 'sta_id',
        ],
        'name' => [
            'name' => 'sta_name',
            'rules' => 'max:50|required',
        ],
        'internalName' => [
            'name' => 'sta_internal_name',
            'rules' => 'max:50|required',
        ],
        'code' => [
            'name' => 'sta_code',
            'rules' => 'max:6|required',
        ],
        'needPostcode' => [
            'name' => 'sta_need_post_code',
            'rules' => 'max:1|required',
        ], 
    ];

    /**
     * getStateNameFromStateId
     *
     * @param [int] $countryId
     * @return string
     */
    public static function getStateNameFromStateId($stateId)
    {
        $state = State::where('sta_id', '=',  $stateId)->get()->toArray();

        if(!empty($state) && !empty($state[0]['sta_name'])) {
            
            return $state[0]['sta_name'];

        }
    }
    
}