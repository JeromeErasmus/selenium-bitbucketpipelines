<?php
/**
 * Created by PhpStorm.
 * User: thimira.gunasekera
 * Date: 5/05/2016
 * Time: 4:13 PM
 */

namespace App\Services\Permissions;

use App\Services\Permissions\PermissionSet;
use Elf\Exception\UnauthorizedException;
use Elf\Http\Request;

class NetworkUserPermissions extends Permissions
{
    
    const STATION_COMMENT_TYPE_ID = "2";
    
    
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

    private function authorizeTvcRequirement(Request $request, $route, PermissionSet $permissions)
    {
        return $this->authorizeStandard($request, $route, $permissions);

    }

    private function authorizeRequirement(Request $request, $route, PermissionSet $permissions)
    {
        return $this->authorizeStandard($request, $route, $permissions);

    }

    private function authorizeOasDashboard(Request $request, $route, PermissionSet $permissions){
        $method = $request->getHttpMethod();

        if ( $method == "GET" ) {
            return true;
        }
        throw new UnauthorizedException("Not authorized to access this resource.");
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
    
    /**
     * 
     * @param Request $request
     * @param type $route
     * @param PermissionSet $permissions
     * @return type
     */
    private function authorizeKeyNumber(Request $request, $route, PermissionSet $permissions) 
    {
        
        $method = $request->getHttpMethod();
        
        switch($method) {
            
            case "GET":
                
                $this->app->request()->inject('includeRequirementCategories', true, true);

                if($permissions->can('all', $route, $method) ) {
                   
                    return;
                    
                }
                
                break;
        }
        
        throw new UnauthorizedException("No permissions granted to access this resource");
        
    }
    
    /**
     * 
     * @param Request $request
     * @param type $route
     * @param PermissionSet $permissions
     * @return type
     */
    private function authorizeAdvertiser(Request $request, $route, PermissionSet $permissions) 
    {
        
        return $this->authorizeStandard($request, $route, $permissions);
        
    }
    
    /**
     * 
     * @param Request $request
     * @param type $route
     * @param PermissionSet $permissions
     * @return type
     * @throws UnauthorizedException
     */
    private function authorizeNetwork(Request $request, $route, PermissionSet $permissions) 
    {

        $method = $request->getHttpMethod();
        
        $networkUser = $this->app->service('User')->getCurrentUser();
        
        $id = $request->query('id');
  
        switch($method) {
            
            case "GET":
                
                if(null === $id && $permissions->can('list', $route, $method) ) {
                   
                    return;
                    
                } else if($id == $networkUser->getNetworkId() && $permissions->can('own', $route, $method) ) {

                    return;
                    
                }
                
                break;
            case "POST":
                    // TODO implement if required
                break;
            case "PATCH":
                    // TODO implement if required
                break;
            case "DELTE":
                    // TODO implement if required
                break;
            default :
                   // TODO implement if required
                break;
            
        }
        
        throw new UnauthorizedException("No permissions granted to access this resource");
        
    }
    
    /**
     * 
     * @param Request $request
     * @param type $route
     * @param PermissionSet $permissions
     * @return type
     * @throws UnauthorizedException
     */
    private function authorizeComment(Request $request, $route, PermissionSet $permissions) 
    {

        $method = $request->getHttpMethod();
        
        $id = $request->query('id');
        
        $this->app->request->inject('commentTypeFilter', self::STATION_COMMENT_TYPE_ID);

        switch($method) {
            
            case "GET":
                
                if($request->query('commentType') != self::STATION_COMMENT_TYPE_ID) {
                    
                    throw new UnauthorizedException("No permissions granted to access this resource");
                    
                }

                if(null === $id && $permissions->can('station', $route, $method) ) {
                   
                    return;
                    
                } else if($permissions->can('station', $route, $method)) {
                    
                    // this is a single comment
                    return;
                    
                }
                
                break;
            case "POST":
                
                if($permissions->can('station', $route, $method) &&
                        $request->request('type') == self::STATION_COMMENT_TYPE_ID) {
                    
                        return;
                        
                    }
                    
                    break;
            case "PATCH":
                
                if($permissions->can('station', $route, $method)) {
                    
                    return;
                    
                }
                
                break;
            case "DELETE":
                    // TODO implement if required
                break;
            default :
                   // TODO implement if required
                break;
        }
        
        throw new UnauthorizedException("No permissions granted to access this resourcex");
        
    }

    /**
     * Allows the network user to retrieve the user model particular to them when they successfully login
     * @param Request $request
     * @param $route
     * @param \App\Services\Permissions\PermissionSet $permissions
     * @return bool
     */
    public function authorizeLogin(Request $request, $route, PermissionSet $permissions)
    {

        $method = $request->getHttpMethod();

        if ($method == "GET") {
            return true;
        }

    }

    /**
     * 
     * @param Request $request
     * @param type $route
     * @param PermissionSet $permissions
     * @return type
     * @throws UnauthorizedException
     */
    private function authorizeStandard(Request $request, $route, PermissionSet $permissions) 
    {

        $method = $request->getHttpMethod();
        
        switch($method) {
            
            case "GET":
                //remove draft from network users as they do not have a draft permission
                if(!$permissions->can('draft', $route, $method)){
                    $request->inject('draft', 0, true);
                }
                
                if($permissions->can('station', $route, $method) ) {
                   
                    return;
                    
                }
                
                break;
            case "POST":
                    // TODO implement if required
                break;
            case "PATCH":
                    // TODO implement if required
                break;
            case "DELTE":
                    // TODO implement if required
                break;
            default :
                   // TODO implement if required
                break;
            
        }
        
        throw new UnauthorizedException("No permissions granted to access this resource");
        
    }

}