<?php

namespace App\Models;

use App\Models\EloquentModel;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Description of TvcFormats
 *
 * @author adam
 */
class Network extends EloquentModel
{

    use SoftDeletes;

    /**
     * override the default primary key
     */
    protected $primaryKey = "net_id";
    protected $table = 'networks';
    public $timestamps = false;

    protected $fieldMap = [
        'id' => [
            'name' => "net_id",
        ],
        'networkCode' => [
            'name' => 'net_code',
            'rules' => 'max:50|required',
        ],
        'networkName' => [
            'name' => 'net_name',
            'rules' => 'max:50|required',
        ],
    ];

}