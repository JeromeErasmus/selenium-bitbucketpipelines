<?php

/**
 * Description of TvcFormat
 *
 * @author adam
 */

namespace App\Controllers;

use App\Models\EloquentModel;
use App\Models\Requirement;
use App\Models\ValidationManager;
use Elf\Http\Request;
use Elf\Event\RestEvent;
use Elf\Exception\NotFoundException;
use App\Models\TvcRequirement as Model;
use Elf\Utility\Convert;


class TvcRequirement extends AppRestController {

    private $route = "tvcRequirement";

    /**
     * @param Request $request
     * @return \App\Models\type|array
     * @throws NotFoundException
     *
     */
    public function handleGet(Request $request)
    {
        $tvcId = $request->query('tvcId');
        $jobId = $request->query('jobId');
        $reqId = $request->query('reqId');
        $id = $request->query('id');
        $conditions = array();
        $capsule = $this->app->service('eloquent')->getCapsule();
        $model = new Model();
        $requirementMdl = new Requirement();
        if(null !== $tvcId || null !== $jobId || null !== $reqId) {
            if (null !== $tvcId) {
                $conditions['rtv_tvc_id'] = $tvcId;
            }
            if (null !== $jobId) {
                $conditions['rtv_job_id'] = $jobId;
            }
            if (null !== $reqId) {
                $conditions['rtv_req_id'] = $reqId;
            }

            $results = $model->where($conditions)->with('listRequirements')->get()->toArray();
            $data = [];
            foreach ($results as $tvcRequirement) {
                $tmp = $model->convertUsingFieldMap($tvcRequirement);
                $listRequirement = $tvcRequirement['listRequirements'][0];
//                var_dump($listRequirement); exit;
                $tmp['requirement'] = $requirementMdl->convertUsingFieldMap($listRequirement);
                try {
                    $tmp['requirement']['createdBy'] =  $this->app->service('User')->retrieveUserDetails($tmp['requirement']['createdBy']);
                    $tmp['requirement']['modifiedBy'] =  $this->app->service('User')->retrieveUserDetails($tmp['requirement']['modifiedBy']);
                    if($tmp['requirement']['createdBy'] && array_key_exists('userPermissionSet',$tmp['requirement']['createdBy'])) {
                        unset($tmp['requirement']['createdBy']['userPermissionSet']);
                    }
                    if($tmp['requirement']['modifiedBy'] && array_key_exists('userPermissionSet',$tmp['requirement']['modifiedBy'])) {
                        unset($tmp['requirement']['modifiedBy']['userPermissionSet']);
                    }
                } catch (NotFoundException $e) {
                    // not a fatal error e.g. user was deleted
                }

                $data[] = $tmp;
            }
            return $data;
        }
        if(null !== $id) {
            $model = Model::findOrFail($id);
            return $model->getAsArray();
        } else {
            throw new NotFoundException('Please enter an ID');
        }
    }


    public function handlePost(Request $request)
    {
        $now = new \DateTime();
        $capsule = $this->app->service('eloquent')->getCapsule();
        $userInput = $request->retrieveJSONInput();
        $model = new Model();

        $userInput['cadVisible'] = isset($userInput['cadVisible']) ? Convert::toBoolean($userInput['cadVisible']) : false;
        $userInput['activityReportVisible'] = isset($userInput['activityReportVisible']) ? Convert::toBoolean($userInput['activityReportVisible']) : false;
        $userInput['mandatory'] = isset($userInput['mandatory']) ? Convert::toBoolean($userInput['mandatory']) : false;
        $userInput['satisfied'] = isset($userInput['satisfied']) ? Convert::toBoolean($userInput['satisfied']) : false;


        $model->setFromArray($userInput);

        if($model->validate()) {
            $model->save();
            $data = $model->getAsArray();
            $clientId = $request->query('clientId');
            $url = "/" . $this->route .  "/clientId/$clientId/id/" . $data['id'];
            $this->set('locationUrl', $url);
            $this->set('status_code', 201);
            return;
        }

        $this->set('status_code', 400);

        return $model->errors;

    }

    public function handlePatch(Request $request)
    {
        $id = $request->query('id');
        if(null === $id) {
            throw new NotFoundException("No id specified");
        }
        $capsule = $this->app->service('eloquent')->getCapsule();
        $userInput = $request->retrieveJSONInput();
        $model = Model::findOrFail($id);
        $model->setFromArray($userInput);
        if($model->validate()) {
            $model->save();
            $this->set('status_code', 204);
            return;
        }
        $this->set('status_code', 400);

        return $model->errors;
    }


    public function handleDelete(Request $request)
    {
        $id = $request->query('id');
        if(null === $id) {
            throw new NotFoundException("No id specified");
        }

        $capsule = $this->app->service('eloquent')->getCapsule();
        $deleted = Model::destroy($id);

        if($deleted) {
            $this->set('status_code', 204);
            return;
        }

        throw new NotFoundException("Couldn't delete the tvc requirement");
    }

    public function convertToRestfulAndRetrieveUserDetails($results)
    {
        $results = EloquentModel::arrayToRestful($results);
        foreach($results as $index => $result) {
            foreach ($result['listRequirements'] as $requirementIndex => $requirement) {
                if (!empty($requirement['reqCreateUserId'])) {
                    $results[$index]['listRequirements'][$requirementIndex]['reqCreateUserId'] = $this->app->service('User')->retrieveUserDetails($requirement['reqCreateUserId']);
                }
                if (!empty($requirement['reqModifyUserId'])) {
                    $results[$index]['listRequirements'][$requirementIndex]['reqModifyUserId'] = $this->app->service('User')->retrieveUserDetails($requirement['reqModifyUserId']);
                }
            }
        }
        return $results;
    }
}
