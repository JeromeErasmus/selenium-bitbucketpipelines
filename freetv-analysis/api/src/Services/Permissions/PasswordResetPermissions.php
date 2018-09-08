<?php

namespace App\Services\Permissions;

use App\Services\Permissions\PermissionSet;
use Elf\Exception\UnauthorizedException;
use Elf\Http\Request;

class PasswordResetPermissions extends Permissions
{
    
    
    /**
     * 
     * @param PermissionSet $permission
     * @throws \Exception
     */
    public function loadPermission(PermissionSet $permission) {

        if ($permission == null) {
            
            throw new \Exception("Permission cannot be empty");
            
        }

        $this->permission = $permission;
        
    }

    /**
     * 
     * @param type $route
     * @param type $event
     * @param type $app
     * @return type
     * @throws UnauthorizedException
     */
    public function handlePermission($route, $event, $app)
    {
        
        if ($this->permission->isRouteMethodAllowed($route['controller'], $app->request()->getHttpMethod()) !== true) {

            throw new UnauthorizedException("Route permission invalid");
            
        }

        if ($this->permission->can('any', $route['controller'], $app->request()->getHttpMethod())) {
            
            return;
            
        }
        
        $fn = "authorize" . ucfirst($route['controller']);
        if (method_exists($this, $fn)) {
            
            $this->$fn($app->request(), $route['controller'], $this->permission);
            
            return;
            
        }

        $entities = $this->permission->getEntityName($route['controller']);

        if ($this->authorizeDefault($entities, $this->permission, $app->request(), $route['controller'])) {
            
            return;
            
        }

        throw new UnauthorizedException("Route permission invalid");

    }
    
    /**
     * Default handler for permissions - defaults to access denied.
     * @param type $entities
     * @param type $permission
     * @param type $request
     * @param type $route
     * @throws UnauthorizedException
     */
    private function authorizeDefault($entities, $permission, $request, $route) 
    {
        
        throw new UnauthorizedException("Route permission invalid");
        
    }
    
    /**
     * 
     * @param Request $request
     * @param type $route
     * @param PermissionSet $permissions
     * @return type
     */
    private function authorizeJob(Request $request, $route, PermissionSet $permissions)
    {

        return $this->authorizeStandard($request, $route, $permissions);

    }


    private function in_array_r($needle, $haystack, $strict = false) {
        foreach ($haystack as $item) {
            if (($strict ? $item === $needle : $item == $needle) || (is_array($item) && $this->in_array_r($needle, $item, $strict))) {
                return true;
            }
        }

        return false;
    }

    // Throw exception if :
    // * No user id
    // * More than one key trying to be patched
    // * The one key is not one of the keys defined in the permissions
    // * that means as of 8/09/16 implementation, you can reset a password OR link an agency OR make a user a freelancer
    // * but only one of those actions

    private function authorizeAgencyUser(Request $request, $route, PermissionSet $permissions)
    {
        $method = $request->getHttpMethod();
        $userId = $request->query('userId');
        $email = $request->query('email');

        $permissionKeys = $this->permission->getPermission($route);

        if($userId != null || $email != null) {

            switch($method) {

                case "GET" :

                    return;

                case "PATCH":

                    $requestBody = $request->retrieveJSONInput();
                    $key = array_keys($requestBody);

                    if(count($requestBody) == 1) {
                        if($this->in_array_r($key[0],$permissionKeys['PATCH'])) {
                            return;
                        }
                    }

                    break;

            }

        }
        
        throw new UnauthorizedException("No permissions granted to access this resource");
        
    }

    /**
     * Authorize retrieving a single agencies details if
     * - An agency id is set
     * - The request does not ask for cc tokens
     * @param Request $request
     * @param $route
     * @param \App\Services\Permissions\PermissionSet $permissions
     * @throws UnauthorizedException
     * @throws \Elf\Exception\MalformedException
     * @throws \Exception
     */
    private function authorizeAgency(Request $request, $route, PermissionSet $permissions)
    {
        $method = $request->getHttpMethod();
        $agencyId = $request->query('agencyId');
        $retrieveTokens = $request->query('withTokens');

        if($agencyId != null && $retrieveTokens != 1) {

            if($method == "GET") {
                return;
            }

        }

        throw new UnauthorizedException("No permissions granted to access this resource");

    }

}