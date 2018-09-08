<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Services;

use Elf\Security\SecurityInterface;
use Elf\Exception\UnauthorizedException;


/**
 * Description of Authentication
 * This service checks the incoming request for a clientId parameter and delegates to the appropriate
 * athentication service. 
 * @todo abstract the client/authentication service pairings to the configuration
 * @author michael
 */
class Authentication implements SecurityInterface {
    
    private $authenticationService;
    
    /**
     * delegate to an athentication service depending on the clientId specified in the request
     * @param type $app
     * @throws Exception
     * @throws UnauthorizedException
     */
    public function __construct($app)
    {
        $request = $app->request();

        $components = $app->config->get('components');
        $credentials = $request->getBasicAuthFromHeaders();

        /*
         * case 1 = CAD user 
         * case 2 = Agency User
         * case 3 = Network User
         * case 4 = OAS password reset
         */
        switch($request->query('clientId')) {
            case 1:
                if($credentials[0] !=  $components['Ads']['restrictedAdsUsername']) {
                    $this->authenticationService = $app->service('AdsAuthentication');
                    break;
                }
                throw new UnauthorizedException("Required clientId not present or invalid in request");
            case 2:
                $this->authenticationService = $app->service('AgencyUserAuthentication');
                break;
            case 3:
                $this->authenticationService = $app->service('NetworkUserAuthentication');
                break;
            case 4:
                $this->authenticationService = $app->service('AdsAuthentication');
                break;
            default:
                throw new UnauthorizedException("Required clientId not present or invalid in request");
        }
    }
    
    /**
     * @inheritDoc
     */
    public function authenticateUser()
    {
        $this->authenticationService->authenticateUser();
    }
    
}
