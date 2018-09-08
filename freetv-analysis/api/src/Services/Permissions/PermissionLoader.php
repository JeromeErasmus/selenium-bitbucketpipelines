<?php
/**
 * Created by PhpStorm.
 * User: thimira.gunasekera
 * Date: 5/05/2016
 * Time: 4:45 PM
 */

namespace App\Services\Permissions;

class PermissionLoader
{
    const CAD_USER = "1";
    const AGENCY_USER = "2";
    const NETWORK_USER = "3";
    const ADMIN_UTILITIES = "4";

    /**
     * @param $request
     * @param $config
     * @return AgencyUserPermissions|CadUserPermissions|NetworkUserPermissions
     * @throws \Exception
     *
     * Load permission and set. Pass in request and config to figure out what / which permission set to load
     * e.g. from db if needed....
     */
    public static function loadPermission($request, $config, $app)
    {
        switch ($request->query('clientId')) {
            case self::CAD_USER:   //cad users
                return new CadUserPermissions($app);
            case self::AGENCY_USER:   // agency users

                $agencyPermission = new AgencyUserPermissions($app);

                $permissionSet = new PermissionSet(require($config->configPath.'/agencyPermissions.php'));

                $agencyPermission->loadPermission($permissionSet);

                return $agencyPermission;

            case self::NETWORK_USER:       //network users
                
                $networkPermission = new NetworkUserPermissions($app);

                $permissionSet = new PermissionSet(require($config->configPath.'/networkPermissions.php'));

                $networkPermission->loadPermission($permissionSet);

                return $networkPermission;

            case self::ADMIN_UTILITIES:       // OAS Admin user resetting passwords or linking agencies

                $networkPermission = new PasswordResetPermissions($app);

                $permissionSet = new PermissionSet(require($config->configPath.'/passwordResetPermissions.php'));

                $networkPermission->loadPermission($permissionSet);

                return $networkPermission;
                
            default:
                throw new \Exception("Invalid clientId");
        }

    }
}