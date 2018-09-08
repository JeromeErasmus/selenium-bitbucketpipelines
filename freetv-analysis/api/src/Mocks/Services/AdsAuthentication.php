<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Mocks\Services;

use \App\Services\AdsAuthentication as RealAdsAuthentication;


class AdsAuthentication extends RealAdsAuthentication {

    public function __construct($app)
    {
        parent::__construct($app);

    }

}
