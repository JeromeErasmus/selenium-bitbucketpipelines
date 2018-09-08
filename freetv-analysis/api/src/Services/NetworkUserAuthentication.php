<?php
/**
 * Created by PhpStorm.
 * User: thimira.gunasekera
 * Date: 18/03/2016
 * Time: 11:01 AM
 */

namespace App\Services;


use Elf\Db\AbstractParent;
use Elf\Exception\UnauthorizedException;
use Elf\Security\SecurityInterface;

class NetworkUserAuthentication extends AbstractParent implements SecurityInterface
{
    public function __construct($app)
    {
        $this->app = $app;
        $this->request = $app->request;

        $this->config = $app->config->get('security');
    }

    public function authenticateUser()
    {
        $credentials = $this->request->getBasicAuthFromHeaders();

        $networkUser = $this->app->model('NetworkUser');

        $username = $credentials[0];
        $password = $credentials[1];

        if (empty($username) || empty($password)) {
            throw new UnauthorizedException("Username or password not given");
        }

        $sql = "SELECT user_sysid, id, email_address, password FROM network_users WHERE email_address = :email AND active = 1";
        
        $details = $this->fetchOneAssoc($sql, [':email' => $username]);

        if (empty($details)) {
            throw new UnauthorizedException('User not Authenticated for this request');
        }

        $correctPwd = $details['password'];

        if ($this->checkPassword($password, $correctPwd, $this->config['HashAlg']) === true) {
            $networkUser->setLastLogin($details['user_sysid']);
            return true;
        }

        throw new UnauthorizedException('User not Authenticated for this request');
    }

    private function checkPassword($password, $correctPwd, $hashMethod)
    {
        switch ($hashMethod)
        {
            case 'plaintext':
                return ($password === $correctPwd);
            default:
                if (in_array($hashMethod, hash_algos())) {

                    return (hash($hashMethod, $password) === $correctPwd);

                } else {
                    throw new \Exception("Config error - unknown hash");
                }
        }
        return false;
    }

}