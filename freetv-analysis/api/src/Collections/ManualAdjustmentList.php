<?php
namespace App\Collections;

use App\Models\ManualAdjustment as Model;

/**
 * TvcFormatList
 * @author adam
 */
class ManualAdjustmentList implements AppCollectionInterface
{
    private $conditions;

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
        'deletedAt' => [
            'name' => 'deleted_at',
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
    ];

    public function __construct($app)
    {
        $this->app = $app;
    }

    public function getAll()     //don't think this is needed
    {
        $data = [];
        $capsule = $this->app->service('eloquent')->getCapsule();
        if(!empty($this->conditions)) {
            $results = Model::where($this->conditions)->get();
        } else {
            $results = Model::all();
        }
        foreach($results as $result) {
            $data[] = $result->getAsArray();
        }
        return $data;
    }

    public function fetch() {}

    public function setParams($params = array())
    {
        //fixes eloquent, uneloquently -- mchan
        unset($params['clientId']);

        foreach($params as $key => $param) {
            $this->conditions[$this->fieldMap[$key]['name']] = $param;
        }
        // Unless explicitly defined, only return unprocessed manual adjustments
        if(!array_key_exists('processed',$params)) {
            $this->conditions['processed'] = 0;
        }
    }

}
