<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Services;
use Elf\Db\AbstractParent;

class AdsCache extends AbstractParent {

    public function authenticate($username, $password)
    {
        
        $sql = "SELECT user_password FROM dbo.users WHERE user_id = :username AND users.deleted <> 1";
        $correctPwd = $this->fetchOneAssoc($sql, array(':username' => $username))['user_password'];
        
        $components = $this->app->config->get('components');
        
        switch ($components['AdsCache']['HashAlg'])
        {
            case 'plaintext':
                    return ($password === $correctPwd);
            default:
                if (in_array($components['AdsCache']['HashAlg'], hash_algos())) {
                    
                    return (hash($components['AdsCache']['HashAlg'], $password) === $correctPwd);
                    
                } else {
                    throw new \Exception("Config error - unknown hash");
                } 
        }                
        return false;
    }
}
