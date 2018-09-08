<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


namespace App\Controllers;


use Elf\Http\Request;
use Elf\Event\RestEvent;
use App\Models\MyMetaModel;


class UserConstants extends RestEvent {
    
    public function handleGet(Request $request)
    {
        // @TODO authorize requestNo response received
        $id = $request->query('id');

        return get_defined_constants(true)['user'];
    }
}