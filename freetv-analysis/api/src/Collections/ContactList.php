<?php
/**
 * Created by PhpStorm.
 * User: jeremy.paul
 * Date: 14/10/2015
 * Time: 1:02 PM
 */

namespace App\Collections;

use App\Models\Contact as Model;
use Elf\Exception\NotFoundException;


class ContactList
{
    private $params = [];

    public $filterArray;

    public function __construct($app)
    {
        $this->app = $app;
    }

    public function getAll()     //don't think this is needed
    {
        $data = [];
        $capsule = $this->app->service('eloquent')->getCapsule();
        foreach(Model::where($this->filterArray)->with('notifications')->get() as $contact) {
            $data[] = $contact->toRestful();
        }
        if (empty($data) ) {
            throw new NotFoundException("No data for specified request");
        }
        return $data;
    }

    public function fetch() {}

    public function setParams($params = array())
    {
        $contact = new Model();
        if(isset($params['contactableType'])) {
            $params['contactableType'] = 'App\Models\\' . $params['contactableType'];
        }
        $this->filterArray = $contact->retrieveFilterArray($params);
    }

}