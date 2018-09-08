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
use App\Models\Comment as Model;
use Illuminate\Support\Facades\App;
use App\Models\JobAlert;

/**
 * Class Comment
 * @package App\Controllers
 *
 * NOTES:
 * Going out (GET): type of comment is by param 'commentType'
 *
 * Going in (POST/PATCH): type of comment is by param 'type'
 *
 *
 */
class Comment extends AppRestController {

    private $entity = "comment";

    /**
     *
     * @param Request $request
     * @return type
     */
    public function handleGet(Request $request)
    {

        $commentType = $request->query('commentType');

        $id = $request->query('id');
        $capsule = $this->app->service('eloquent')->getCapsule();

        $model = Model::with('commentType', 'commentReplyType', 'replies')->orderBy('created_at','desc');

        if ($id !== null) {

            if($request->query('commentTypeFilter')) {

                $data = $model->where('type', '=' ,$request->query('commentTypeFilter'))->find($id);

                if(null === $data) {
                    throw new NotFoundException('No comment found for the requesting user with id ' . $id);
                }

            } else {
                $data = $model->findOrFail($id);
            }

            $data->setApp($this->app);

            return $data->toRestful();
        }

        $params = $this->app->request()->query();

        // Enforce that requests to this api must have a type and reference id in the request.
        if(!empty($params['commentType']) && !empty($params['refId'])) {
            $model->where('type', '=', $params['commentType']);
            $model->where('ref_id', '=', $params['refId']);
            $data = [];
            foreach($model->get() as $entity) {
                $entity->setApp($this->app);
                $entityData = $entity->toRestful();
                $data[] = $entityData;
            }
            return $data;
        }
        throw new \Exception('Please have a commentType and refId in your request');
    }


    public function handlePost(Request $request)
    {
        $constants = $this->app->config->get('commentType');
        $now = new \DateTime();
        $capsule = $this->app->service('eloquent')->getCapsule();
        $userInput = $request->retrieveJSONInput();
        $userInput['createdAt'] = $now->format('Y-m-d H:i:s');
        $userInput['createdBy'] = $this->app->service('user')->getCurrentUser()->getUserSysid();

        if(isset($userInput['jobId'])) {
            $userInput['refId'] = $userInput['jobId'];      // keep old stuff working
        }

        $model = new Model();
        $model->setFromArray($userInput);

        if($model->validate()) {
            $model->save();
            $data = $model->getAsArray();
            $clientId = $request->query('clientId');
            $url = "/" . $this->entity .  "/clientId/$clientId/id/" . $data['id'];
            $this->set('locationUrl', $url);
            $this->set('status_code', 201);

            $commentTypes = $this->app->config->get('commentType');

            if ($data['type'] == $commentTypes['job']){
                $commentType = 'agency';
            } else if ($data['type'] == $commentTypes['station']) {
                $commentType = 'station';
            } else if ($data['type'] == $commentTypes['requirement']) {
                $commentType = 'requirement';
            }

            /* email reply notifications hook */
            if (isset($data['parent']) && $data['parent'] != null && isset($commentType)) {
                $scriptExecutable = "php " . ASYNC_SCRIPT. " AsyncNotificationSendout "
                    ."--ENVIRONMENT=".getenv("ENVIRONMENT") . " "
                    ."--notificationType=".$commentType."Comment "
                    ."--commentId=".$data['id']. " --replyType=".$data['replyType'];
                $this->requestAsync($scriptExecutable);
            }
            /* Network Comment Notification hook */
            if (isset($commentType) && $commentType=='station' && $data['parent'] == null) {

                $job = $this->app->model( 'Job' );
                $job->setJobId($userInput['refId']);
                $job->load();
                $jobData = $job->getFullJob();

                $alert = array(
                    'alertMessage' => 'A station has posted a comment on a job you own',
                    'alertDestinationUserId' => $jobData['owner'],
                    'alertSourceUserId' => '',
                    'jobId' => $userInput['refId']
                );

                $capsule = $this->app->service('eloquent')->getCapsule();
                $jobAlert = new JobAlert();
                $jobAlert->setFromArray($alert);
                $jobAlert->save();

            }

            return;
        }

        $this->set('status_code', 400);

        return $model->errors;
    }

    public function handlePatch(Request $request)
    {
        $now = new \DateTime();
        $id = $request->query('id');
        if(null === $id) {
            throw new NotFoundException("");
        }
        $capsule = $this->app->service('eloquent')->getCapsule();
        $userInput = $request->retrieveJSONInput();
        $userInput['updatedAt'] = $now->format('Y-m-d H:i:s');
        $model = Model::findOrFail($id);
        $model->setFromArray($userInput);

        if($request->query('commentTypeFilter') && $request->query('commentTypeFilter') != $model->type) {
            throw new NotFoundException('No comment found for the requesting user with id ' . $id);
        }

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
            throw new NotFoundException("");
        }

        $capsule = $this->app->service('eloquent')->getCapsule();
        $deleted = Model::destroy($id);

        if($deleted) {
            $this->set('status_code', 204);
            return;
        }

        throw new NotFoundException("");
    }

}
