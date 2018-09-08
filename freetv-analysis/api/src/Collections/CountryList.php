<?php

namespace App\Collections;

use App\Models\Country as Model;

/**
 * TvcFormatList
 * @author adam
 */
class CountryList implements AppCollectionInterface
{

    public function __construct($app)
    {
        $this->app = $app;
    }

    public function getAll()     //don't think this is needed
    {
        $data = [];
        $capsule = $this->app->service('eloquent')->getCapsule();
        $results = Model::orderBy('cty_name')->get();
        foreach($results as $result) {
            $data[] = $result->getAsArray();
        }
        return $data;
    }

    public function fetch() {}

    public function setParams($params = array()) {}

}
