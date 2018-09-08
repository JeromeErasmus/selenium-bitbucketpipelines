<?php

namespace App\Controllers;

use Elf\Exception\ConflictException;
use Elf\Exception\NotFoundException;
use Elf\Http\Request;
use Elf\Event\RestEvent;


class TvcExtract extends RestEvent {
        /**
     * 
     * @param Request $request
     * @return type
     */
    public function handleGet(Request $request)
    {
        $days = $request->query('days');
        $export = $request->query('export');

        $data = array();
        if(null !== $days && null !== $export) {
            $tvcExtract = $this->app->model('TvcExtract');
            $tvcExtract->setDays($days);
            $data['data'] = $tvcExtract->load();
            if (!empty($data['data'])) {
                $data['headers'] = $tvcExtract->headers($data['data']);
                $this->set('status_code', 200);
                return $data;
            }
        }

    }
}
