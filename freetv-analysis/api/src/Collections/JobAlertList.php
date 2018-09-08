<?php
/**
 * Created by PhpStorm.
 * User: jeremy.paul
 * Date: 14/01/2016
 * Time: 4:04 PM
 */

namespace App\Collections;

use App\Models\JobAlert as Model;

class JobAlertList {
    private $conditions;

    public function __construct($app)
    {
        $this->app = $app;
    }

    public function getAll()     //don't think this is needed
    {
        $data = [];
        $capsule = $this->app->service('eloquent')->getCapsule();
        $results = Model::all();
        foreach($results as $result) {
            $data[] = $result->getAsArray();
        }
        return $data;
    }

    public function fetch()
    {
        $data = [];
        $capsule = $this->app->service('eloquent')->getCapsule();
        $results = Model::where($this->conditions)->get();
        foreach($results as $result) {
            $data[] = $result->getAsArray();
        }
        return $data;
    }

    /**
     * @param array $params
     * Only return unread alerts
     */
    public function setParams($params = array())
    {
        $this->conditions['alert_read_status'] = '0';
        if (isset($params['userId'])) {
            $this->conditions['alert_destination_user_id'] = $params['userId'];
        }
    }
}