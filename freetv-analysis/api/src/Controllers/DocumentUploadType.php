<?php

/**
 * Description of TvcFormat
 *
 * @author adam
 */

namespace App\Controllers;

use Elf\Http\Request;
use Elf\Event\RestEvent;
use App\Models\DocumentUploadType as Model;


class DocumentUploadType extends RestEvent {

    /**
     * 
     * @param Request $request
     * @return type
     */
    public function handleGet(Request $request) 
    {
        $id = $request->query('id');
        $capsule = $this->app->service('eloquent')->getCapsule();
        if ($id != null) {
            $model = Model::findOrFail($id);
            return $model->toArray();
        }

        $data = Model::all();
        $uploadTypes = array();
        foreach ($data as $item) {
            $uploadTypes[] = $item->toArray();
        }

        return $uploadTypes;
    }

}
