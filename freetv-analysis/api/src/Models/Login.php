<?php
/**
 * Created by PhpStorm.
 * User: thimira.gunasekera
 * Date: 31/08/2015
 * Time: 11:16 AM
 */

namespace App\Models;
use Elf\Application\Application;

class Login extends \Elf\Db\AbstractAction
{

    public function getUserInfo($userId)
    {
        $query = "SELECT
          user_id,
          user_name,
          user_first_name,
          user_last_name,
          user_email,
          user_jfi_id,
          user_role_id,
          user_permission_set
          FROM dbo.users WHERE user_id = :userId
          AND deleted <> 1";

        $user = $this->fetchOneAssoc($query, array(
            ':userId' => $userId
        ));
        if (!empty($user['user_role_id']) ) {
            $query = "SELECT role_name, role_slug FROM dbo.roles WHERE role_id = '{$user['user_role_id']}'";
            $role = $this->fetchOneAssoc($query);
        } else {
            throw new \Exception("No role id");
        }

        $result = array(
            'userId' => $user['user_id'],
            'userName' => $user['user_name'],
            'userFirstName' => $user['user_first_name'],
            'userLastName' => $user['user_last_name'],
            'userEmail' => $user['user_email'],
            'userJfiId' => $user['user_jfi_id'],
            'userPermissionSet' => json_decode($user['user_permission_set'], true),
            'userRole' => array(
                'id' => $user['user_role_id'],
                'roleName' => $role['role_name'],
                'roleSlug' => $role['role_slug']
            )
        );
        return $result;

    }





    public function load(){}
    public function save(){}


}