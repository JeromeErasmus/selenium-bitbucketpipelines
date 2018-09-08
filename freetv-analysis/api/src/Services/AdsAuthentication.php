<?php
namespace App\Services;

use Elf\Db\AbstractParent;
use Elf\Security\SecurityInterface;
use Elf\Exception\UnauthorizedException;
use Elf\Exception\NotFoundException;
use App\Models\User;
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of AdsAuthentication
 *
 * @author michael
 */
class AdsAuthentication implements SecurityInterface {
    
    protected $app;
    protected $type;
    protected $authMethod;

    public function __construct($app)
    {
        $this->app = $app;
        $this->request = $app->request;
        $this->authMethod = $app->service('Ads');
        if (!$this->authMethod->isConnected) {
            $this->authMethod = $app->service('AdsCache');
        }
        
        // if this ads is not connected
        // this ads = table auth component
        $this->config = $app->config->get('security');
    }
    
    //put your code here
    public function authenticateUser()
    {
        $authenticated = false;
        $credentials = array();
        // check if password auth then auth using appropriate method
        switch($this->config['type'])
        {
            case 'password' :
            default : 
                $credentials = $this->request->getBasicAuthFromHeaders();
                $authenticated = $this->authMethod->authenticate($credentials[0], $credentials[1]);
                break;
        }
        if(!$authenticated)
        {
            throw new UnauthorizedException('User not Authenticated for this request');
        }  
        
        // update the user password locally if they exists
        try
        {
            $user = new User($this->app);
            $user = $user->findOneByUserId($credentials[0]);
            $user->setUserPassword($credentials[1]);
            $user->setLastLogin();
            $user->save();
        }
        catch (NotFoundException $exception)
        {
            //assume this is a new user and create them or follow some other business rules.
            $userInfo = $this->authMethod->user()->infoCollection($credentials[0], array('*'));
            $defaultRoleId = !empty($this->app->config->get('security')['DefaultRoleId']) ? $this->app->config->get('security')['DefaultRoleId'] : '200';     //backwards compat.
            $userData = array(
                'userId' => $credentials[0],
                'userFirstName' => $userInfo->givenName,
                'userLastName' => $userInfo->SN,
                'userFullName' => $userInfo->displayName,
                'userEmail' => $userInfo->email,
                'userActive' => '1',
                'userJfiId' => '1',
                'userAllowUpdates' =>  '1',
                'userRoleId' => $defaultRoleId,
                'userPermissionSet' => '{}'
            );

            $user->setUserPassword($credentials[1]);
            $user->setFromArray($userData);
            $user->save();
        }
        catch (\Exception $exception)
        {
            throw $exception;
        }
    }
}
