<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Controllers;


use Elf\Http\Request;
use Elf\Event\RestEvent;

/**
 * Description of Job
 *
 * @author michael
 */
class Login extends RestEvent
{
    private $credentials;

    /**
     * Returns the user data according to which client id they send in
     * @param Request $request
     * @return mixed
     * @throws UnauthorizedException
     * @throws \Elf\Exception\UnauthorizedException
     * @throws \Exception
     */
    public function handleGet(Request $request)
    {
        $this->credentials = $request->getBasicAuthFromHeaders();

        switch($request->query('clientId')) {
            case 1:
                return $this->retrieveUserDetails();
            case 2:
                return $this->retrieveAgencyUserDetails();
            case 3:
                return $this->retrieveNetworkUserDetails();
            default:
                throw new UnauthorizedException("Required clientId not present or invalid in request");
        }
    }

    /**
     * Retrieve CAD user details
     * @return mixed
     * @throws \Exception
     */
    public function retrieveUserDetails()
    {
        $user = $this->app->model('user')->findOneByUserId($this->credentials[0]);
        $userInfo = $user->getAsArray();
        $role = $this->app->model('role')->findOneByRoleId($userInfo['userRoleId']);
        $userInfo["userRole"] = $role->getAsArray();
        unset($userInfo['userRoleId']);
        unset($userInfo['userRole']['rolePermissionSet']);

        if($userInfo && !empty($userInfo)) {
            return $userInfo;
        }
        throw new \Exception("Could not find user profile");

    }

    /**
     * Retrieve agency user details
     * @return mixed
     * @throws \Exception
     */
    public function retrieveAgencyUserDetails()
    {
        $agencyUserModel = $this->app->model('agencyUser');
        $agencyUserModel = $agencyUserModel->findOneByEmail($this->credentials[0]);
        $agencyUserId = $agencyUserModel->getUserId();

        $userInfo = $agencyUserModel->getAgencyUserById($agencyUserId);

        if(!empty($userInfo)) {
            return $userInfo;
        }
        throw new \Exception("Could not find user profile");
    }

    /**
     * Retrieve network user details
     * @return mixed
     * @throws \Exception
     */
    public function retrieveNetworkUserDetails()
    {
        $networkUser = $this->app->model('NetworkUser');
        $networkUser = $networkUser->findOneByEmail($this->credentials[0]);
        $userInfo = $networkUser->getAsArray();

        if(!empty($userInfo)) {
            return $userInfo;
        }
        throw new \Exception("Could not find user profile");
    }
}
