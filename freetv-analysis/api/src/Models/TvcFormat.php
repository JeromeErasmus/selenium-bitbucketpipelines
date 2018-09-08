<?php

namespace App\Models;

use App\Models\EloquentModel;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Description of TvcFormats
 *
 * @author adam
 */
class TvcFormat extends EloquentModel
{
    
    use SoftDeletes;
    
    /**
     * override the default primary key
     */
    protected $primaryKey = "tfo_id";
    protected $table = 'tvc_formats';
    public $timestamps = false;
    
    /**
     * this is required for soft deleting
     * @var type 
     */
    protected $dates = ['deleted_at'];

    protected $fieldMap = [
        'id' => [
            'name' => 'tfo_id',
        ],
        'name' => [
            'name' => 'tfo_name',
            'rules' => 'max:50|required',
        ],
        'internalName' => [
            'name' => 'tfo_internal_name',
            'rules' => 'max:50|required',
        ],
        'code' => [
            'name' => 'tfo_code',
            'rules' => 'max:6|required',
        ],  
    ];
    
}