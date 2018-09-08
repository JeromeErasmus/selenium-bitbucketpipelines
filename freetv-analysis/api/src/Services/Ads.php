<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Services;
use adLDAP\adLDAP;
/**
 * Description of Ads
 *
 * @author michael
 */
class Ads extends adLDAP {
    
    public $isConnected;
    private $components;
    
    public function __construct($app)
    {
        $this->app = $app;
        $components = $app->config->get('components');
        $this->components = $components;
        $adsConfig = array();
        if(array_key_exists('Ads', $components))
        {
            $adsConfig = $components['Ads'];
        }
        $this->isConnected = $this->checkConnection($adsConfig);
        parent::__construct($adsConfig);
    }


    public function checkConnection($adsConfig)
    {
        $adsMdl = $this->app->model('Ads');

        $time_last = $adsMdl->getLastFailedTime();
        $now = new \DateTime();
        $interval = ($now->getTimestamp() - $time_last->getTimestamp()) / 60;

        if (empty($this->components['AdsCache']['recheckTime'])) {
            throw new \Exception("Cannot find ADS recheck time in config");
        }

        if ($interval < $this->components['AdsCache']['recheckTime']) {        // Ads has failed inside the timeout, so don't check ADS again and use cache
            return false;
        } else if ($this->actuallyCheckConnection($adsConfig) === true) {       // everything ok, continue
            return true;
        } else {                    // ADS has to connect and it wasn't inside the timeout period, so log it and use cache
            $adsMdl->insertFail();
            return false;
        }
    }



    /**
     * @param $adsConfig
     * @return bool
     * @throws \Exception
     * adLDAP does not check for connection until authentication. This does.
     *
     * This function actually tries to connects to ADS, takes 5 seconds so only run this every x minutes
     */
    public function actuallyCheckConnection($adsConfig)
    {
        if (!isset($adsConfig['domain_controllers'])) {
            throw new \Exception("Cannot find domain controller");
        }
        $ldapIdentifier = ldap_connect($adsConfig['domain_controllers'][array_rand($adsConfig['domain_controllers'])]);
        ldap_set_option($ldapIdentifier, LDAP_OPT_NETWORK_TIMEOUT, 3);

        if(@ldap_bind($ldapIdentifier)){
            @ldap_close($ldapIdentifier);
            return true;
        }

        return false;

    }

}
