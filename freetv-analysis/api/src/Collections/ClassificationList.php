<?php
/**
 * Created by PhpStorm.
 * User: jeremy.paul
 * Date: 14/10/2015
 * Time: 1:02 PM
 */

namespace App\Collections;

use App\Models\Classification as Model;


class ClassificationList {

    public function __construct($app)
    {
        $this->app = $app;
    }

    public function getAll()     //don't think this is needed
    {
        $capsule = $this->app->service('eloquent')->getCapsule();
        $data = Model::get();
        $data = Model::arrayToRestful($data);

        return $data;
    }

    public function fetch() {}

    public function setParams($params = array()) {}

}