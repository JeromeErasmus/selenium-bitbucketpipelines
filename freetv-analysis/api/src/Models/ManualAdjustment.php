<?php

namespace App\Models;

use App\Models\EloquentModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Elf\Utility\Convert;

/**
 * Description of TvcFormats
 *
 * @author adam
 */
class ManualAdjustment extends EloquentModel
{

    use SoftDeletes;

    /**
     * override the default primary key
     */
    protected $primaryKey = "id";
    protected $table = 'manual_adjustments';
    public $timestamps = false;

    /**
     * this is required for soft deleting
     * @var type
     */
    protected $dates = ['created_at','updated_at','deleted_at'];

    protected $hidden = ['deleted_at'];

    protected $fieldMap = [
        'id' => [
            'name' => "id",
        ],
        'maType' => [
            'name' => 'type_id',
            'rules' => 'required'
        ],
        'maDescription' => [
            'name' => 'description',
        ],
        'createdAt' => [
            'name' => 'created_at',
        ],
        'updatedAt' => [
            'name' => 'updated_at',
        ],
        'maAmount' => [
            'name' => 'amount',
        ],
        'jobId' => [
            'name' => 'job_id',
        ],
        'processed' => [
            'name' => 'processed',
        ],
        'transactionId' => [
            'name' => 'transaction_id',
        ],
        'gst' => [
            'name' => 'gst',
        ],
        'exgst' => [
            'name' => 'ex_gst',
        ],
    ];
    
    /**
     * get the manual adjustments by transaction id that have been processed
     * use example: $manualAdjObj->processed(<transaction_id>)->get()->toArray()
     * @param type $query
     * @param type $id transaction id
     */
    public function scopeProcessed($query, $id)
    {
         $query->where(['transaction_id' => $id, 'processed' => 1]);
    }
}
