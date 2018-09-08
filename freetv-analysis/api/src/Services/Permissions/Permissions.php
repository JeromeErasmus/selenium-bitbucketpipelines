<?php
/**
 * Created by PhpStorm.
 * User: thimira.gunasekera
 * Date: 5/05/2016
 * Time: 3:28 PM
 */
namespace App\Services\Permissions;

use App\Models\Requirement;
use App\Models\TvcRequirement;
use App\Models\Contact;
use App\Services\Permissions\PermissionSet;
use Elf\Core\Module;
use Elf\Exception\NotFoundException;
use Elf\Exception\UnauthorizedException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

abstract class Permissions extends Module {

    protected $permission;

    abstract public function handlePermission($route, $event, $app);

    public function loadPermission(PermissionSet $permission) {

        if ($permission == null) {
            throw new \Exception("Permission cannot be empty");
        }

        $this->permission = $permission;
    }

    /**
     * @param $agencyId
     * @param $agencyUserId
     * @return bool
     *
     * Checks if the agency user is secondary linked to the given agencyId
     */
    protected function isLinkedAgency($agencyId, $agencyUserId) {

        $agencyUser = $this->app->model('AgencyUser')->getAgencyUserById($agencyUserId);

        if (empty($agencyUser['relatedAgencies'])) {
            return false;
        }

        foreach ($agencyUser['relatedAgencies'] as $agency) {
            if (!empty($agency['agencyId']) && $agency['agencyId'] == $agencyId ) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param $agencyId
     * @param $agencyUserId
     * @return bool
     *
     * Returns true if given agency has the agency user as the primary
     */
    protected function isPrimaryAgency($agencyId, $agencyUserId) {

        $agencyUser = $this->app->model('AgencyUser')->getAgencyUserById($agencyUserId);

        if (!empty($agencyUser['agency']['agencyId']) && $agencyUser['agency']['agencyId'] == $agencyId) {
            return true;
        }

        return false;
    }

    protected function isAgencyAdmin($agencyId, $agencyUserId)
    {
        $agencyUser = $this->app->model('AgencyUser')->getAgencyUserById($agencyUserId);

        if (!empty($agencyUser['isAgencyAdmin']) && !empty($agencyUser['agency']['agencyId']) && $agencyUser['agency']['agencyId'] == $agencyId) {
            return true;
        }

        return false;

    }

    /**
     * @param array $agencyUsers
     * @return bool
     *
     * Checks if all the given agency user Ids belong to the same agency as their primary agency
     */
    protected function isSameAgency($agencyUsers = array()) {
        if (empty($agencyUsers)) {
            return false;
        }

        $agencyUser = $this->app->model('AgencyUser')->getAgencyUserById($agencyUsers[0]);

        if ( empty($agencyUser['agencyId']) ) {
            return false;
        } else {
            $agencyId = $agencyUser['agencyId'];
        }

        array_shift($agencyUsers);

        foreach ($agencyUsers as $user) {
            $tempUser = $this->app->model('AgencyUser')->getAgencyUserById($user);
            if (!isset($tempUser['agencyId'])) {
                //check the related agencies too
                foreach($tempUser['relatedAgencies'] as $relatedAgencies){
                    if($relatedAgencies['agencyId'] == $agencyUser['agencyId']){
                        return true;
                    }
                }
                return false;
            }

            if ($tempUser['agencyId'] != $agencyId) {
                return false;
            }
        }

        return true;

    }

    /**
     * @param $jobId
     * @param $agencyUserId
     * @return array|bool (false if no relationship, else array of relationships)
     * @throws \Exception
     *
     * Given a jobId and agencyUserId, find out what the relationship of it is
     * e.g. no relationship, own (submitted), primary agency, linked agency
     */
    protected function getJobRelationship($jobId, $agencyUserId ) {

        if (empty($jobId) || empty($agencyUserId)) {
            throw new \Exception("No job id or agencyUserId given");
        }

        $relationships = array();       //no relationships

        $job = $this->app->model('job');
        $job->setJobId($jobId);
        $job->load();

        $jobData = $job->getFullJob();
        $agencyUser = $this->app->model('AgencyUser')->getAgencyUserById($agencyUserId);

        if ($jobData['createdBy'] == $agencyUser['userSysid']) {
            $relationships[] = 'own';
        }

        if (isset($jobData['agency']['agencyId']) && $jobData['agency']['agencyId'] == $agencyUser['agencyId']) {
            $relationships[] = 'agency';
        }

        if (empty($agencyUser['relatedAgencies'])) {
            return $relationships;
        }

        foreach ($agencyUser['relatedAgencies'] as $agency) {
            if (isset($jobData['agency']['agencyId']) && $agency['agencyId'] == $jobData['agency']['agencyId']) {
                $relationships[] = 'linked';
                break;
            }
        }

        return $relationships;

    }

    /**
     * @param $reqId
     * @return mixed
     * @throws UnauthorizedException
     *
     * Get the job which the requirement belongs to
     *
     */
    protected function getRequirementJob($reqId)
    {
        $this->app->service('eloquent')->getCapsule();
        try {
            $requirement = Requirement::findOrFail($reqId)->getAsArray();
            return $requirement['jobId'];
        } catch (NotFoundException $e) {
            throw new UnauthorizedException("Not authorized to access this resource.");
        }
    }


    /**
     * Returns either the job or agency id for an agency or the job id for a job
     * @param $contactId
     * @return mixed
     * @throws UnauthorizedException
     */
    protected function getContactEntityId($contactId)
    {
        $this->app->service('eloquent')->getCapsule();
        try {
            $comment = Contact::findOrFail($contactId)->getAsArray();
            return $comment['contactableId'];
        } catch (\Exception $e) {
            throw new UnauthorizedException("Not authorized to access this resource.");
        }

    }

    protected function getTvcRequirementJob($tvcReqId)
    {
        $this->app->service('eloquent')->getCapsule();
        try {
            $tvcRequirement = TvcRequirement::findOrFail($tvcReqId)->getAsArray();
            return $tvcRequirement['jobId'];
        } catch (NotFoundException $e) {
            throw new UnauthorizedException("Not authorized to access this resource.");
        }
    }

    /**
     * Returns the job id for a given key number
     * @param $keyNumberId
     * @return mixed
     * @throws UnauthorizedException
     */
    protected function getJobFromKeyNumber($keyNumberId)
    {
        $keyNumberModel = $this->app->model('KeyNumber');
        $keyNumberModel->setTvcId($keyNumberId);
        $keyNumberModel->load();
        if ($keyNumberData = $keyNumberModel->getAsArray()) {
            return $keyNumberData['jobId'];
        }
        throw new UnauthorizedException("Not authorized to access this resource.");
    }

    /**
     * @param $documentId
     * @return mixed
     * @throws UnauthorizedException
     */
    protected function getDocumentInfo($documentId)
    {
        try {

            $document = $this->app->model('document');
            $document->setDocumentId($documentId);

            return $document->load()[0];       //NotFoundException will be caught before indexing error

        } catch (NotFoundException $e) {
            throw new UnauthorizedException("Not authorized to access this resource.");
        }

    }

    protected function getKeyNumberJobId($keyNumberId)
    {
        try {
            $keyNumberModel = $this->app->model('KeyNumber');
            $keyNumberModel->setTvcId($keyNumberId);
            $keyNumberModel->load();

            return $keyNumberModel->getJobId();
        } catch (NotFoundException $e) {
            throw new UnauthorizedException("Not authorized to access this resource.");
        }
    }


}