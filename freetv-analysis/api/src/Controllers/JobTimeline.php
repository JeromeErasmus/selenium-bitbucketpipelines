<?php
/**
 * Created by PhpStorm.
 * User: jeremy.paul
 * Date: 18/12/2015
 * Time: 11:15 AM
 */

namespace App\Controllers;

use Elf\Event\RestEvent;
use Elf\Http\Request;

class JobTimeline extends RestEvent{

    public function handleGet(Request $request)
    {
        $id = $request->query('id');
        $data = array();
        if ($id !== null) {
            $jobTimeline = $this->app->model('JobTimeline');
            $jobTimeline->setJobId($id);
            $jobTimeline->setRequest($request);
            $data = $jobTimeline->retrieveTimeline();

            $this->set('status_code', 200);
            return $data;
        }
    }

}