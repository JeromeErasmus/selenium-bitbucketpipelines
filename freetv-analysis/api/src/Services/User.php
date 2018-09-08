<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Services;

use Elf\Core\Module;
use Elf\Exception\NotFoundException;
/**
 * Description of User
 *
 * @author michael
 */
class User extends Module {
    
    private $currentUser = null;
    
    /**
     * initialize configuration for the Eloquent capsule
     */
    public function init()
    {       
        $credentials = $this->app->request()->getBasicAuthFromHeaders();

        switch($this->app->request()->query('clientId')) {
            case "1":
                $this->currentUser = $this->app->model('user')->findOneByUserId($credentials[0]);
                break;
            case "2":
                $this->currentUser = $this->app->model('AgencyUser')->findOneByEmail($credentials[0]);
                break;
            case "3":
                $this->currentUser = $this->app->model('NetworkUser')->findOneByEmail($credentials[0]);
                break;
            default:
                throw new \Exception("Invalid clientId");

        }
    }
    
    public function getCurrentUser() 
    {
        if(null === $this->currentUser) {
            throw new NotFoundException("could not find curent user");
        }
        return $this->currentUser;
    }

    public function retrieveUserDetails($userIdentifier)
    {
        $sysUsers = $this->app->model('SystemUsers');

        $data = null;
        if (!empty($userIdentifier)) {
            if (is_numeric($userIdentifier)) {

                $userType = $sysUsers->getUserType($userIdentifier);

                // string in database rather than an Id, I know :(
                switch($userType) {
                    case "users":
                        $user = $this->app->model('user')->findOneByUserSysId($userIdentifier)->getAsArray();
                        break;
                    case "agency users":
                        $user = $this->app->model('AgencyUser')->getAgencyUserBySysId($userIdentifier);
                        break;
                    case "network users":
                        $user = $this->app->model('NetworkUser')->findBySysId($userIdentifier);
						$user = $user->getAsArray();
                        //@todo
                        break;
                    default:
                        throw new \Exception("Invalid user type in system users table");
                }
            } else {
                $user = $this->app->model('user')->findOneByUserId($userIdentifier)->getAsArray();
            }
            $data = $user;
        }
        return $data;
    }
}
