<?php
namespace App\Collections;

use App\Models\OrderForm as Model;

/**
 * TvcFormatList
 * @author adam
 */
class OrderFormList implements AppCollectionInterface
{
    private $conditions;

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
        foreach($params as $key => $param) {
            $this->conditions[$this->fieldMap[$key]['name']] = $param;
        }
    }

}
