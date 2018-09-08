<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Config;

use Elf\Config\Config as BaseConfig;

class Config extends BaseConfig {

    /**
     * Override the constructor to add the doc root dynamically
     * @inheritDoc
     */
    public function __construct()
    {

        $this->defaults['paths']['documentRoot'] = $_SERVER['DOCUMENT_ROOT'];
        $this->defaults['paths']['services'] = substr(__DIR__,0,strrpos(__DIR__,DIRECTORY_SEPARATOR)) . DIRECTORY_SEPARATOR . 'Services';

        $this->configPath = __DIR__;

        // for TVC extract
        $this->withdrawnEventType = 'W';
        $this->rejectedEventType = 'R';
        //CAD length
        $this->cadLength = 4;
        // invoice types
        $this->manualInvoice = 2;
        $this->standardInvoice = 1;


        $this->westpacTransactionApproved  = 0;
        $this->westpacTransactionDeclined = 1;
        $this->westpacTransactionError = 2;
        $this->westpacTransactionRejected = 3;
        
        $this->unknownRecheckCountLimit = 2;

        parent::__construct();

        $this->qvalentParams = array(
            'logDirectory' => '.',
            'url' => $this->config['qvalentParams']['url'],
            'certificateFile' => $this->defaults['paths']['documentRoot'].DIRECTORY_SEPARATOR.'FREETV_2362002.pem',
            'caFile' =>  $this->defaults['paths']['documentRoot'].DIRECTORY_SEPARATOR.'cacerts.crt',
        );
    }

}
