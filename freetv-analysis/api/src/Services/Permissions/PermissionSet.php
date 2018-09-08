<?php
/**
 * Created by PhpStorm.
 * User: thimira.gunasekera
 * Date: 6/05/2016
 * Time: 9:40 AM
 */

namespace App\Services\Permissions;

class PermissionSet
{
    private $permissionSet = array();
    private $entityNames = array();

    /**
     * @param $set
     * @throws \Exception
     *
     * Load permission set (array)
     */
    public function __construct($set)
    {
        if (!isset($set['allowedRoutes']) || !isset($set['entityNames']) ) {
            throw new \Exception("Allowed routes or entity names not set in permission set");
        }
        $this->permissionSet['allowedRoutes'] = array_change_key_case($set['allowedRoutes'], CASE_LOWER);
        $this->entityNames = array_change_key_case($set['entityNames'], CASE_LOWER);
    }

    /**
     * @param $requestedActions
     * @param $route
     * @param $method
     * @return bool
     * @throws \Exception
     *
     * Checks to see if the loaded permission set allows for the action $do
     * e.g. Single permission check: can the user type A list jobs would be $obj->can('list', 'job') === true
     *
     * e.g. Multiple permission check: can user type A list own or jobs from agency $obj->can(['own', 'agency'], 'job') === true
     *
     */
    public function can($requestedActions, $route, $method)
    {
        $permissions = $this->getPermission($route);

        $method = strtoupper($method);

        if ( empty($route) || empty($method)) {
            throw new \Exception("method or route cannot be empty in permission check");
        }

        if ( empty($requestedActions) ) {
            return false;
        }

        if (!is_array($requestedActions)) {
            $requestedActions = [$requestedActions];
        }

        if (isset($permissions[$method])) {

            $allowedActions = $permissions[$method];

            return $this->isActionAllowed($requestedActions, $allowedActions);
        }

        return false;
    }


    /**
     * @param $route
     * @param $method
     * @return bool
     *
     * Checks the agency permission set if the route and method is allowed
     *
     */
    public function isRouteMethodAllowed($route, $method) {
        if (empty($route) || empty($method)) {
            return false;
        }

        foreach($this->permissionSet['allowedRoutes'] as $routeName => $allowedMethods) {
            if (strtolower($route) === strtolower($routeName)) {
                if(isset($allowedMethods[$method])) {
                    return (bool)$allowedMethods[$method];
                }
                return false;
            }
        }

        return false;
    }

    /**
     * @param $route
     * @param null $method
     * @return mixed
     * @throws \Exception
     *
     * Gets the allowed permissions for a route, or if a method was given for that permission
     */
    public function getPermission($route, $method = null) {
        if (!empty($this->permissionSet['allowedRoutes'][strtolower($route)])) {
            if ($method === null) {
                return $this->permissionSet['allowedRoutes'][strtolower($route)];
            } else if (isset($this->permissionSet['allowedRoutes'][strtolower($route)][$method]) ) {
                return $this->permissionSet['allowedRoutes'][strtolower($route)][$method];
            }

        }
        throw new \Exception("Cannot find permission set for requested route");
    }

    /**
     * @param $requested
     * @param $available
     * @return bool
     *
     * Checks if the requested action is in the available array set
     */
    private function isActionAllowed($requested, $available) {

        foreach ($requested as $action) {
            if (!in_array($action, $available)) {
                return false;
            }
        }

        return true;

    }

    public function getEntityName($route, $type = null)
    {
        $route = strtolower($route);
        
        if ( empty($this->entityNames[$route]) ) {
            throw new \Exception("No entities for given route");
        }

        if ($type == null) {
            return $this->entityNames[$route];
        } else if (isset($this->entityNames[$route][$type])) {
            return $this->entityNames[$route][$type];
        }

        throw new \Exception("No entity name with given type");

    }


}