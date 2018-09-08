<?php
/**
 * Created by PhpStorm.
 * User: thimira.gunasekera
 * Date: 8/03/2016
 * Time: 2:20 PM
 */

namespace App\Services;


use Elf\Db\AbstractParent;
use Elf\Exception\UnauthorizedException;
use Elf\Security\SecurityInterface;
use App\Utility\OldCryptography;

class AgencyUserAuthentication extends AbstractParent implements SecurityInterface
{
    protected $app;
    protected $type;
    protected $authMethod;

    public function __construct($app)
    {
        $this->app = $app;
        $this->request = $app->request;

        $this->config = $app->config->get('security');
    }

    public function authenticateUser()
    {
        $credentials = $this->request->getBasicAuthFromHeaders();

        $agencyUser = $this->app->model('AgencyUser');

        $username = $credentials[0];
        $password = $credentials[1];

        if (empty($username) || empty($password)) {
            throw new UnauthorizedException("Username or password not given");
        }

        $sql = "SELECT agu_sysid, agu_email_address, agu_password FROM agency_users WHERE agu_email_address = :email AND agu_is_active = 1";

        $details = $this->fetchOneAssoc($sql, [':email' => $username]);

        if (empty($details)) {
            throw new UnauthorizedException('User not Authenticated for this request');
        }

        $correctPwd = $details['agu_password'];

        if ($this->checkPassword($password, $correctPwd, $this->config['HashAlg']) === true || $this->checkOldPassword($password, $correctPwd) === true ) {
            $agencyUser->setLastLogin($details['agu_sysid']);
            return true;
        }

        throw new UnauthorizedException('User not Authenticated for this request');
    }

    /**
     * @param $password
     * @param $correctPwd
     * @param $hashMethod
     * @return bool
     * @throws \Exception
     *
     * Encrypts the $passwordd according to the hash method and then checks against the correct, encrypted password
     */
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

    /**
     * @param $password
     * @param $correctPwd
     * @return bool
     *
     * This is used for legacy agency users to be able to log in
     */
    private function checkOldPassword($password, $correctPwd) {

        return bin2hex(str_pad($password, 16, "\07")) === bin2hex(OldCryptography::decrypt($correctPwd)); // decrypt pads with \07

    }

}