<?php
/**
 * Created by PhpStorm.
 * User: jeremy.paul
 * Date: 8/01/2016
 * Time: 3:39 PM
 */

namespace App\Controllers;

use Elf\Http\Request;
use Elf\Event\RestEvent;
use Elf\Exception\NotFoundException;
use App\Models\LogTypes as Model;
use App\Models\EloquentModel;

class LogTypes extends RestEvent {

    public function handleGet(Request $request)
    {
        $id = $request->query('id');
        $capsule = $this->app->service('eloquent')->getCapsule();
        if(null === $id)
        {
            $model = Model::all();
            return EloquentModel::arrayToRestful($model);
        }

        $model = Model::findOrFail($id);
        return $model->getAsArray();
    }
}