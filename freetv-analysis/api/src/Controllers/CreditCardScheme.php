<?php
/**
 * Created by PhpStorm.
 * User: mchan
 * Date: 8/08/16
 * Time: 8:55 AM
 */

namespace App\Controllers;

use Elf\Http\Request;
use Elf\Event\RestEvent;
use Elf\Exception\NotFoundException;
use App\Models\CreditCardScheme as Model;

class CreditCardScheme extends RestEvent
{

    /**
     * @param Request $request
     * @return mixed
     */
    public function handleGet(Request $request)
    {
        $capsule = $this->app->service('eloquent')->getCapsule();
        return Model::all();;
    }

    public function handlePost(Request $request)
    {
        throw new NotFoundException(['displayMessage' => 'This is not a valid end point']);
    }

    public function handlePatch(Request $request)
    {
        throw new NotFoundException(['displayMessage' => 'This is not a valid end point']);
    }

}