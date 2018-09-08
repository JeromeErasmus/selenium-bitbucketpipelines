<?php
/**
 * Created by PhpStorm.
 * User: thimira.gunasekera
 * Date: 5/05/2016
 * Time: 4:12 PM
 */

namespace App\Services\Permissions;

use App\Services\Permissions\PermissionSet;
use Elf\Exception\NotFoundException;
use Elf\Exception\UnauthorizedException;
use Elf\Http\Request;

class AgencyUserPermissions extends Permissions
{
    const REQUIREMENT_COMMENT_TYPE = 3;
    const JOB_COMMENT_TYPE = 1;

    const TVC_UPLOAD = 1;
    const AGENCY_UPLOAD = 2;
    const INTERNAL_UPLOAD = 3;
    const SYSTEM_UPLOAD = 4;
    const SCRIPT_UPLOAD = 5;

    public function handlePermission($route, $event, $app)
    {
        // check the client id in request
        // if 2 then proceed
        // get the authenticated user
        // retrieve permissions for this requested api endpoint
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

    private function authorizeCollections($entities, PermissionSet $permission, $request, $route)
    {
        $singleId = null;
        $method = $request->getHttpMethod();

        if (isset($entities['single']) && is_string($entities['single'])) {
            $singleId = $request->query($entities);
        }


        if ($method == "GET" && $singleId == null && $permission->can('list', $route, $method) ) {

            return;

        }

        throw new \Exception("Not authorized to retrieve these entities.");



    }

    private function authorizeAdvertiser(Request $request, $route, PermissionSet $permissions){
        $method = $request->getHttpMethod();

        if ( $method == "GET" ) {
            return true;
        }
        else if ( $method == "POST" ) {
            return true;
        }
        else {
            throw new UnauthorizedException("Not authorized to access this resource.");
        }
    }

    private function authorizeOasDashboard(Request $request, $route, PermissionSet $permissions){
        $method = $request->getHttpMethod();

        if ( $method == "GET" ) {
            return true;
        }
        throw new UnauthorizedException("Not authorized to access this resource.");
    }

    private function authorizeAgency(Request $request, $route, PermissionSet $permissions ) {

        $agencyUser = $this->app->service('User')->getCurrentUser()->getAgencyUser();
        $agencyUserId = $agencyUser['userId'];

        $method = $request->getHttpMethod();
        $agencyId = $request->query('agencyId');

        if ( $method == "GET" ) {

            if ($agencyId != null) {        //single entity

                if ( $this->isPrimaryAgency($agencyId, $agencyUserId) && $permissions->can(['single', 'agency'], $route, $method) ) {
                    return true;
                }
                if ( $this->isLinkedAgency($agencyId, $agencyUserId) && $permissions->can(['single', 'linked'], $route, $method) ) {
                    return true;
                }
            } else {
                $agencyName = $request->query('agencyName');
                if($agencyName != null) {
                    $request->inject('restrict','autocomplete', true );  // Force autocomplete permissions on them
                    return true;
                }
                throw new UnauthorizedException("Agency name required - agencyName=example");
            }


        } else if ( $method == "PATCH" ) {

            $requestBody = $request->retrieveJSONInput();

            if ($permissions->can('own-admin', $route, $method)) {

                if (!empty($agencyUser['agency']['agencyId']) && $agencyUser['agency']['agencyId'] == $agencyId) {      //only agency admins can modify their own agency
                    return true;
                }

                if (!empty($requestBody['linkAgencyUser']) && $agencyUser['isAgencyAdmin'] == true) {
                    return true;
                }

            }
        }
        else if ($method == "POST") {
            return true;
        }
        throw new UnauthorizedException("Not authorized to access this resource.");

    }

    /**
     * @param Request $request
     * @param $route
     * @param \App\Services\Permissions\PermissionSet $permissions
     * @return bool
     * @throws UnauthorizedException
     * @throws \Exception
     *
     * Authorize agency user
     */
    private function authorizeAgencyUser(Request $request, $route, PermissionSet $permissions )
    {
        $agencyUser = $this->app->service('User')->getCurrentUser()->getAgencyUser();
        $agencyUserId = $agencyUser['userId'];

        $method = $request->getHttpMethod();
        $agencyId = $request->query('agencyId');
        $userId = $request->query('userId');
        $email = $request->query('email');

        if ($method == "GET") {

            if ($email != null) {
                throw new UnauthorizedException("Not authorized to access this resource.");
            }

            if ($userId != null) {      //single entity

                if ($this->isSameAgency([$agencyUserId, $userId]) && $permissions->can(["single", "own", "agency"], $route, $method ) ) {
                    return true;
                } else if ($agencyUserId == $userId && $permissions->can(['single', 'own'], $route, $method)) {     //can get their own
                    return true;
                }

            } else if ( $agencyId != null ) {        //collection
                if ($this->isPrimaryAgency($agencyId, $agencyUserId) && $permissions->can(['list', 'agency'], $route, $method ) ) {
                    return true;
                }

            } else {
                $request->inject('agencyId',$agencyUser['agencyId'], true );  // unless they have 'any' permissions, force inject
                return true;
            }

        } else if ($method == "PATCH") {

//            $request->unsetParam('agenciesToLink', $method);    // can only unlink, can't link to a diff agency
            $request->unsetParam('agencyId', $method);      //cannot change primary agency

            if ($agencyUserId != $userId && $agencyUser['isAgencyAdmin'] === true ) {     // trying to modify other agency user details and admin
                if ($this->isSameAgency([$agencyUserId, $userId]) && $permissions->can('agency', $route, $method)) {
                    return true;
                }
            } else if ($agencyUserId == $userId && $permissions->can('own', $route, $method)) {
                return true;
            }
        }
        else if ($method == "POST") {
            return true;
        }

        throw new UnauthorizedException("Not authorized to access this resource.");
    }

    private function authorizeChargeCode(Request $request, $route, PermissionSet $permissions)
    {
        $method = $request->getHttpMethod();

        if ($method == "GET") {
            $now = new \DateTime('now');
            // Inject the restriction to force OAS users to only get job specific charge codes
            $request->inject('restrict', 'OAS', true );      // agency|own|linked format
            $request->inject('job', '1', true );      // agency|own|linked format
            $request->inject('submittedDate', $now->format('Y-m-d'), true );      // agency|own|linked format
            return;
        }
        throw new UnauthorizedException("Not authorized to access this resource.");
    }
    
    private function authorizeTvcFormat(Request $request, $route, PermissionSet $permissions)
    {
        $method = $request->getHttpMethod();

        if ($method == "GET") {
            return;
        }
        throw new UnauthorizedException("Not authorized to access this resource.");
    }

    private function authorizeComment(Request $request, $route, PermissionSet $permissions)
    {
        $agencyUser = $this->app->service('User')->getCurrentUser()->getAgencyUser();
        $agencyUserId = $agencyUser['userId'];

        $method = $request->getHttpMethod();
        $refId = $request->query('refId');
        $commentType = $request->query('commentType');

        if ($method == "GET") {

            if ($commentType === null || $refId === null) {
                throw new UnauthorizedException("Not authorized to access resources without specifying comment type or refId");
            }

            if ($commentType == self::JOB_COMMENT_TYPE && $permissions->can('agency-comment', $route, $method)) {

                $relationship = $this->getJobRelationship($refId, $agencyUserId);

                if ( $permissions->can($relationship, $route, $method)) {
                    return true;
                }

            }

            if ($commentType == self::REQUIREMENT_COMMENT_TYPE && $permissions->can('requirement-comment', $route, $method)) {
                $jobId = $this->getTvcRequirementJob($refId);
                $relationship = $this->getJobRelationship($jobId, $agencyUserId);

                if ($permissions->can($relationship, $route, $method)) {
                    return true;
                }
            }


        } else if ($method == "POST" || $method == "PATCH") {

            $requestBody = $request->retrieveJSONInput();

            if ( empty($requestBody['type']) || empty($requestBody['refId']) ) {
                throw new UnauthorizedException("Comment type and refId required");
            }

            if ($requestBody['type'] == self::JOB_COMMENT_TYPE && $permissions->can('agency-comment', $route, $method)) {

                $relationship = $this->getJobRelationship($requestBody['refId'], $agencyUserId);
                if ( $permissions->can($relationship, $route, $method) ) {
                    return true;
                }
            } else if ($requestBody['type'] == self::REQUIREMENT_COMMENT_TYPE && $permissions->can('requirement-comment', $route, $method) ) {
                $jobId = $this->getTvcRequirementJob($requestBody['refId']);
                $relationship = $this->getJobRelationship($jobId, $agencyUserId);
                if ( $permissions->can($relationship, $route, $method) ) {
                    return true;
                }

            }

        }
        throw new UnauthorizedException("Not authorized to access this resource.");
    }

    private function authorizeCountry(Request $request, $route, PermissionSet $permissionSet){

        $method = $request->getHttpMethod();

        if($method == "GET"){
            return ;
        }
        throw new UnauthorizedException("Not authorized to access this resource.");

    }

    /**
     * Strict requirements on the request parameters are enforced for get patch and delete methods
     * @param Request $request
     * @param $route
     * @param \App\Services\Permissions\PermissionSet $permissions
     * @return bool
     * @throws UnauthorizedException
     * @throws \Elf\Exception\MalformedException
     * @throws \Exception
     */
    private function authorizeContact(Request $request, $route, PermissionSet $permissions)
    {
        $agencyUser = $this->app->service('User')->getCurrentUser()->getAgencyUser();
        $agencyUserId = $agencyUser['userId'];

        $id = $request->query('id');
        $contactableType = $request->query('contactableType');
        $contactableId = $request->query('contactableId');
        $method = $request->getHttpMethod();

        if ($method == "GET") {
            if ($contactableType == null) {
                throw new UnauthorizedException("Contactable type required");
            }
            if ($id == null && $contactableId == null) {
                throw new UnauthorizedException("Contact id or job/agency id required");
            }
            if ($id != null && $permissions->can('single',$route, $method)) {

                if ($contactableType == 'Agencies') {
                    $entityId = $this->getContactEntityId($id);
                    if ( $this->isPrimaryAgency($entityId, $agencyUserId) && $permissions->can(['single', 'agency'], $route, $method) ) {
                        return true;
                    }
                    throw new UnauthorizedException("No permission to access this resource.");
                };

                $relationship = $this->getJobRelationship($contactableId, $agencyUserId);

                if ($permissions->can($relationship, $route, $method)) {
                    return true;
                }

            } else if ($contactableType != null && $contactableId != null && $permissions->can('list',$route, $method)) {

                if ($contactableType == 'Agencies') {
                    if ( $this->isPrimaryAgency($contactableId, $agencyUserId) && $permissions->can(['list', 'agency'], $route, $method) ) {
                        return true;
                    }
                    throw new UnauthorizedException("No permission to access this resource.");
                }
                $relationship = $this->getJobRelationship($contactableId, $agencyUserId);

                if ($permissions->can($relationship, $route, $method)) {
                    return true;
                }
            }

        } else if ($method == "POST") {

            $body = $request->retrieveJSONInput();
            if (empty($body['contactableId'])) {
                throw new UnauthorizedException("Unauthorized to create contact without contactableId");
            }
            if ($body['contactableType'] != 'Job' && $body['contactableType'] != 'Agencies') {
                throw new UnauthorizedException("ContactableType must be 'Job' or 'Agencies'");
            }

            if ($body['contactableType'] == 'Agencies') {
                if ( $this->isAgencyAdmin($body['contactableId'], $agencyUserId) && $permissions->can(['own-admin'], $route, $method) ) {
                    return true;
                }
                throw new UnauthorizedException("No permission to access this resource.");
            }

            $relationship = $this->getJobRelationship($body['contactableId'], $agencyUserId);

            if ($permissions->can($relationship, $route, $method)) {
                return true;
            }

        } else if ($method == "PATCH" || $method == "DELETE") {

            if ($id == null || $contactableType == null) {
                throw new UnauthorizedException("Contact id and contact type required");
            }

            $entityId = $this->getContactEntityId($id);

            if ($contactableType == 'Agencies') {
                if ( $this->isAgencyAdmin($entityId, $agencyUserId) && $permissions->can(['own-admin'], $route, $method) ) {
                    return true;
                }
                throw new UnauthorizedException("No permission to access this resource.");
            }
            $relationship = $this->getJobRelationship($entityId, $agencyUserId);

            if ($permissions->can($relationship, $route, $method)) {
                return true;
            }
        }
        throw new UnauthorizedException("No permission to access this resource.");

    }

    /**
     * Allows the agency user to access the login api, upon which they get their user data returned to them
     * @param Request $request
     * @param $route
     * @param \App\Services\Permissions\PermissionSet $permissions
     * @return bool
     */
    private function authorizeLogin(Request $request, $route, PermissionSet $permissions)
    {
        $method = $request->getHttpMethod();

        if ($method == "GET") {
            return true;
        }

    }

    private function authorizeDocument(Request $request, $route, PermissionSet $permissions) {

        $agencyUser = $this->app->service('User')->getCurrentUser()->getAgencyUser();
        $agencyUserId = $agencyUser['userId'];

        $method = $request->getHttpMethod();
        $jobId = $request->query('id');
        $documentType = $request->query('uploadType');
        $documentId = $request->query('documentId');

        if ($method == "GET") {
            if ($jobId != null && ($documentType != self::SYSTEM_UPLOAD || $documentType != self::INTERNAL_UPLOAD)     // only allow documents uploaded by agency
                && $permissions->can('list',$route, $method)) {

                $relationship = $this->getJobRelationship($jobId, $agencyUserId);

                if ($permissions->can($relationship, $route, $method)) {
                    return true;
                }

            } else if ($documentId != null ){
                $documentInfo = $this->getDocumentInfo($documentId);
                $relationship = $this->getJobRelationship($documentInfo['jum_job_id'], $agencyUserId);

                if (!isset($documentInfo['jum_type_id'])) {
                    throw new UnauthorizedException("Unknown permission error AUP312");
                }

                if (!$permissions->can($relationship, $route, $method)) {
                    throw new UnauthorizedException("No permission to access this resource.");
                }

                if ($documentInfo['jum_type_id'] == self::AGENCY_UPLOAD || $documentInfo['jum_type_id'] == self::SCRIPT_UPLOAD && $permissions->can('agency-upload', $route, $method)){
                    return true;
                }

                if ($documentInfo['jum_type_id'] == self::TVC_UPLOAD && $permissions->can('tvc-upload', $route, $method)){
                    return true;
                }
            }

        } else if ($method == "POST") {

            $body = $request->retrieveJSONInput();

            if (empty($body['jobId']) || empty($body['uploadTypeId']) ) {
                throw new UnauthorizedException("Unauthorized to upload file without jobId or uploadTypeId");
            }

            $relationship = $this->getJobRelationship($body['jobId'], $agencyUserId);

            if ($body['uploadTypeId'] == self::AGENCY_UPLOAD || $body['uploadTypeId'] == self::SCRIPT_UPLOAD) {
                $relationship[] = 'agency-upload';

                if ( $permissions->can($relationship, $route, $method) ) {
                    return true;
                }
            }

            if ($body['uploadTypeId'] == self::TVC_UPLOAD) {

                $relationship[] = 'tvc-upload';

                if ( $permissions->can($relationship, $route, $method) ) {
                    return true;
                }
            }
        }
        throw new UnauthorizedException("No permission to access this resource.");

    }

    /**
     * @param $request
     * @param $permissions
     * @param $route
     * @return bool
     * @throws UnauthorizedException
     * @throws \Exception
     *
     * Authorize job
     */
    private function authorizeJob(Request $request, $route, PermissionSet $permissions)
    {
        $agencyUserId = $this->app->service('User')->getCurrentUser()->getAgencyUser()['userId'];

        $method = $request->getHttpMethod();
        $jobId = $request->query('id');
        $restrictions = $request->query('restrict');

        if ($method == "GET") {

            if ($jobId == null && $permissions->can('list', $route, $method)) {

                if(!$permissions->can('draft', $route, $method)){
                    $request->inject('draft', 0, true);
                }
                
                if($restrictions != null) {
                    if(preg_match('/own|agency|linked/',$restrictions)) {
                        return;
                    }
                }

            } else if ($permissions->can('single', $route, $method)) {

                $relationships = $this->getJobRelationship($jobId, $agencyUserId);

                if ($permissions->can($relationships, $route, $method)) {              // i.e. if they can't view their own jobs, linked jobs, etc.
                    return;
                }
            }

        } else if ($request->getHttpMethod() == "POST") {

            $post = $request->retrieveJSONInput();

            if (empty($post['agencyId'])) {
                throw new \Exception("No agencyId set in post data");   //should never happen if validation works
            }

            $agencyId = $post['agencyId'];

            if ($this->isPrimaryAgency($agencyId, $agencyUserId) && $permissions->can('agency', $route, $method)) {
                return;
            }

            if ($this->isLinkedAgency($agencyId, $agencyUserId) && $permissions->can('linked', $route, $method)) {
                return;
            }
        } else if ( $request->getHttpMethod() == "PATCH" || $request->getHttpMethod() == "DELETE") {

            $relationships = $this->getJobRelationship($jobId, $agencyUserId);

            if ($permissions->can($relationships, $route, $method)) {              // i.e. if they can't view their own jobs, linked jobs, etc.
                return;
            }

        }

        throw new UnauthorizedException("No permissions granted to access this resource");

    }

    private function authorizeJobDeclaration(Request $request, $route, PermissionSet $permissions)
    {
        //todo job declaration permissions
    }

    private function authorizeKeyNumber(Request $request, $route, PermissionSet $permissions)
    {
        $agencyUserId = $this->app->service('User')->getCurrentUser()->getAgencyUser()['userId'];

        $method = $request->getHttpMethod();
        $jobId = $request->query('jobId');
        $id = $request->query('id');

        if($method == "GET") {
            // Don't return anything if they don't give any valid parameters
            if($jobId == null && $id == null) {
                throw new UnauthorizedException("Not authorized to access resources without specifying job id or key number id");
            }
            // Check for permissions for returning a list of key numbers
            if($jobId != null && $permissions->can('list',$route, $method)) {

                $relationship = $this->getJobRelationship($jobId, $agencyUserId);

                if ($permissions->can($relationship, $route, $method)) {
                    return true;
                }
            }
            // Check for permissions on a single key number
            if($id != null) {
                $jobId = $this->getKeyNumberJobId($id);
                $relationship = $this->getJobRelationship($jobId, $agencyUserId);

                if ($permissions->can($relationship, $route, $method)) {
                    return true;
                }
            }
        } else if ($method == "POST") {

            $requestBody = $request->retrieveJSONInput();

            if(empty($requestBody['jobId'])) {
                throw new UnauthorizedException("Job id required");
            }
            // If a revised key number is being made, verify here

            if(!empty($requestBody['originalTvcId'])) {
                $jobId = $this->getKeyNumberJobId($requestBody['originalTvcId']);
                $relationship = $this->getJobRelationship($jobId, $agencyUserId);

                if (!$permissions->can($relationship, $route, $method)) {
                    throw new UnauthorizedException("User not authorized to revise this key number");
                }
            }

            $relationship = $this->getJobRelationship($requestBody['jobId'], $agencyUserId);
            if ( $permissions->can($relationship, $route, $method) ) {
                return true;
            }

        } else if ($method == "PATCH") {
            if($id == null) {
                throw new UnauthorizedException("Not authorized to modify resources without specifying key number id");
            }

            // If a revised key number is being patched in, verify here
            if(!empty($requestBody['originalTvcId'])) {
                $jobId = $this->getKeyNumberJobId($requestBody['originalTvcId']);
                $relationship = $this->getJobRelationship($jobId, $agencyUserId);

                if (!$permissions->can($relationship, $route, $method)) {
                    throw new UnauthorizedException("User authorized to revise this key number");
                }
            }

            $jobId = $this->getKeyNumberJobId($id);
            $relationship = $this->getJobRelationship($jobId, $agencyUserId);

            if ($permissions->can($relationship, $route, $method)) {
                return true;
            }

        }
        throw new UnauthorizedException("No permission to access this resource.");

        //@todo
    }

    /**
     * Always give access to GET requests for network
     * @param Request $request
     * @param $route
     * @param \App\Services\Permissions\PermissionSet $permissions
     * @return bool
     * @throws UnauthorizedException
     */
    private function authorizeNetwork(Request $request, $route, PermissionSet $permissions)
    {
        $method = $request->getHttpMethod();

        if ($method == "GET") {
            return true;
        }
        throw new UnauthorizedException("Not authorized to access this resource.");
    }

    /**
     * Retrieve requirements only if the user has the appropriate permissions on that job
     * @param Request $request
     * @param $route
     * @param \App\Services\Permissions\PermissionSet $permissions
     * @return bool
     * @throws UnauthorizedException
     * @throws \Exception
     */
    private function authorizeRequirement(Request $request, $route, PermissionSet $permissions)
    {
        $agencyUser = $this->app->service('User')->getCurrentUser()->getAgencyUser();
        $agencyUserId = $agencyUser['userId'];
        $id = $request->query('id');

        $method = $request->getHttpMethod();
        $agencyId = $request->query('agencyId');

        if ($method == "GET") {
            if ($id === null) {
                throw new UnauthorizedException("Not authorized to access resources without specifying requirement id");
            }

            $jobId = $this->getRequirementJob($id);

            $relationship = $this->getJobRelationship($jobId, $agencyUserId);

            if ( $permissions->can($relationship, $route, $method)) {
                return true;
            }

        }
        throw new UnauthorizedException("Not authorized to access this resource.");

    }

    private function authorizeTvcRequirement(Request $request, $route, PermissionSet $permissions)
    {
        $agencyUser = $this->app->service('User')->getCurrentUser()->getAgencyUser();
        $agencyUserId = $agencyUser['userId'];
        $jobId = $request->query('jobId');
        $tvcId = $request->query('tvcId');
        $id = $request->query('id');

        $method = $request->getHttpMethod();

        if ($method == "GET") {
            if($id != null && $permissions->can('single',$route, $method)) {
                $jobId = $this->getTvcRequirementJob($id);

                $relationship = $this->getJobRelationship($jobId, $agencyUserId);

                if ($permissions->can($relationship, $route, $method)) {
                    return true;
                }
            } elseif ($jobId != null) {
                $relationship = $this->getJobRelationship($jobId, $agencyUserId);

                if ($permissions->can($relationship, $route, $method)) {
                    return true;
                }
            } elseif ($tvcId != null) {
                $jobId = $this->getJobFromKeyNumber($tvcId);

                $relationship = $this->getJobRelationship($jobId, $agencyUserId);

                if ($permissions->can($relationship, $route, $method)) {
                    return true;
                }
            }
        }
        throw new UnauthorizedException("Not authorized to access this resource.");
    }

}