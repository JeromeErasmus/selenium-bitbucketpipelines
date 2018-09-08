<?php
/**
 * Created by PhpStorm.
 * User: thimira.gunasekera
 * Date: 5/05/2016
 * Time: 4:14 PM
 */

namespace App\Services\Permissions;

class CadUserPermissions extends Permissions
{
    public function loadPermissionSet($params)
    {
        // TODO: Implement loadPermissionSet() method.
    }

    public function handlePermission($route, $event, $app)
    {
        // TODO: Implement handlePermission() method.

        return;    //not dealing with cad permissions atm so just pass through
    }
}