<?php

namespace App\Models;

use App\Models\EloquentModel;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Description of AdviceSlipTemplate
 *
 * @author Jeremy
 */
class AdviceSlipTemplate extends EloquentModel
{
    
    protected $primaryKey = "id";
    protected $table = 'advice_slip_template';
    public $timestamps = false;
    
    /**
     * this is required for soft deleting
     * @var type 
     */
    protected $dates = ['updated_at'];

    protected $fieldMap = [
        'id' => [
            'name' => "id",
        ],
        'adviceTemplate' => [
            'name' => 'advice_template',
            'rules' => 'required',
        ],
    ];
    
}