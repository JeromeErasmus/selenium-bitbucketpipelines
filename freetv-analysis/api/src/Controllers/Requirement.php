<?php

/**
 * Description of TvcFormat
 *
 * @author adam
 */

namespace App\Controllers;

use Elf\Http\Request;
use Elf\Event\RestEvent;
use Elf\Exception\NotFoundException;
use App\Models\Requirement as RequirementModel;
use App\Models\TvcRequirement as TvcRequirementModel;


class Requirement extends RestEvent {

    private $entity = "requirement";

    /**
     *
     * @param Request $request
     * @return type
     * @throws NotFoundException
     */
    public function handleGet(Request $request)
    {
        $id = $request->query('id');
        $capsule = $this->app->service('eloquent')->getCapsule();
        if(null === $id)
        {
            throw new \UnexpectedValueException('Please enter a valid Requirement ID');
        }

        // First get the requirement table data
        try {
            $reqData = RequirementModel::findOrFail($id)->getAsArray();
        } catch (NotFoundException $e) {
            throw new NotFoundException("No requirement with specified ID");
        }


        $data = [];

        try {
            $tvcReqMdl = TvcRequirementModel::where('rtv_req_id', $id)->get();
            // To get an array of associated tvcIds
            $data['tvcId'] = [];
            foreach($tvcReqMdl as $state) {
                $tmp = $state->getAsArray();
                $data['reqId'] = $tmp['reqId'];
                $data['tvcId'][] = $tmp['tvcId'];
            }
        } catch (NotFoundException $e) {        //this requirement has no TVCs, but is still a valid GET
            $data = [];
        }

        $data = array_merge($data, $reqData);
        // Created detail
        try {
            $data['createdBy'] = $this->app->service('User')->retrieveUserDetails($data['createdBy']);
            $data['modifiedBy'] = $this->app->service('User')->retrieveUserDetails($data['modifiedBy']);
        } catch (NotFoundException $e) {
            // not a hard fail if we can't find users
        }

        //Modified details

        unset($data['activityReportVisible']);      //not needed

        return $data;
    }

    /**
     * Undo some of the things JSON.stringify does to rich text.
     * @param $input
     * @return mixed|string
     */
    public function unstringifyInput($input) {
        // Remove the new line characters
        // Remove the start and ending quotes
        $output = substr(str_replace(["\r\n", "\r", "\n", "\\n"], "",$input),1,-1);
        // Unescape the double quote
        $output = str_replace(["\\\\\"","\\\""], "\"",$output);

        return $output;
    }


    public function handlePost(Request $request)
    {
        $capsule = $this->app->service('eloquent')->getCapsule();
        $userInput = $request->retrieveJSONInput();

        $userInput['agencyNotes'] = $this->unstringifyInput($userInput['agencyNotes']);
        $userInput['internalNotes'] = $this->unstringifyInput($userInput['internalNotes']);
        $userInput['stationNotes'] = $this->unstringifyInput($userInput['stationNotes']);

        $now = new \DateTime();
        $userInput['createdAt'] = $now->format('Y-m-d H:i:s');
        $userInput['createdBy'] = $this->app->service('user')->getCurrentUser()->getUserSysid();

        $requirementMdl = new RequirementModel();
        // no  idea why this table needs two references to job ID.
        if (isset($userInput['jobId'])) {
            $requirementMdl->setFromArray([
                "jobId" => $userInput['jobId'],
                "referenceNo" => $userInput['jobId']
            ]);
        }

        $requirementMdl->setFromArray($userInput);

        if($requirementMdl->validate()) {
            $requirementMdl->save();
            $clientId = $request->query('clientId');
            $url = "/" . $this->entity .  "/clientId/$clientId/id/" . $requirementMdl->req_id;
            $this->set('locationUrl', $url);
            $this->set('status_code', 201);
            return;
        }

        $this->set('status_code', 400);
        return $requirementMdl->errors;

    }

    public function handlePatch(Request $request)
    {
        $now = new \DateTime();

        $id = $request->query('id');
        if(null === $id) {
            throw new NotFoundException("No ID supplied");
        }
        $capsule = $this->app->service('eloquent')->getCapsule();
        $userInput = $request->retrieveJSONInput();
        $userInput['updatedAt'] = $now->format('Y-m-d H:i:s');
        $userInput['modifiedBy'] =  $this->app->service('user')->getCurrentUser()->getUserSysid();
        $requirementMdl = RequirementModel::findOrFail($id);
        $requirementMdl->setFromArray($userInput);

        if( $requirementMdl->validate() ) {
            $requirementMdl->save();
            $this->set('status_code', 204);
            return;
        }

        $this->set('status_code', 400);

        return $requirementMdl->errors;
    }


    public function handleDelete(Request $request)
    {
        $id = $request->query('id');
        if(null === $id) {
            throw new NotFoundException("");
        }

        $capsule = $this->app->service('eloquent')->getCapsule();
        $requirementMdl = RequirementModel::destroy($id);

        if($requirementMdl) {
            $this->set('status_code', 204);
            return;
        }

        throw new NotFoundException("Could not delete the requirement");
    }
}
