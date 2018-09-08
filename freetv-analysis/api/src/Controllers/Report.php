<?php
/**
 * Created by PhpStorm.
 * User: jeremy.paul
 * Date: 23/02/2016
 * Time: 3:38 PM
 */

namespace App\Controllers;


use Elf\Event\RestEvent;
use Elf\Exception\MalformedException;
use Elf\Exception\NotFoundException;
use Elf\Http\Request;

class Report extends RestEvent
{
    public function handleGet(Request $request)
    {
        $reportType = $request->query('reportType');
        if(empty($reportType)) {
            throw new NotFoundException("Please enter a Report Type");
        }
        $methodName = 'get' . ucfirst($reportType) . 'Report';
        $params = $request->query();
        $reportModel = $this->app->model('Report');

        if(method_exists($reportModel,$methodName)) {
            $data = $reportModel->$methodName($params);
            $this->set('status_code', 200);
            return $data;
        }
        throw new MalformedException("No Report Type Specified");
    }

    public function handlePost(Request $request)
    {
        try{
            $reportType = $request->query('reportType');
            if($reportType != "dailyActivity") {
                throw new NotFoundException("");
            }
            $params = $request->retrieveJSONInput();
            $reportModel = $this->app->model('Report');

            $reportModel->addDailyActivityRecipients($params['email']);
            $clientId = $request->query('clientId');
            $url = "/report/clientId/$clientId/";

            $this->set('locationUrl', $url);
            $this->set('status_code', 201);

        } catch (MalformedException $e) {
            $this->set('status_code', 400);
            return (array('code' => 400, 'message' => $e->getMessage()));
        } catch (ConflictException $e ) {
            $this->set('status_code', 409);
            return (array('code' => 409, 'message' => $e->getMessage()));
        } catch (Exception $e) {
            $this->set('status_code', 402);
            return (array('code' => 402, 'message' => $e->getMessage()));
        }

    }

    public function handleDelete(Request $request){
        $id = $request->query('recipientId');
        $reportType = $request->query('reportType');
        if($reportType != "dailyActivity") {
            throw new NotFoundException("");
        }

        if (null === $id) {
            throw new \Exception("No recipient ID given");
        }
        try {
            $reportModel = $this->app->model('Report');
            $reportModel->deleteDailyActivityRecipient($id);
        } catch (MalformedException $e) {
            $this->set('status_code', 400);
            return (array('code' => 400, 'message' => $e->getMessage()));
        }
        $this->set('status_code', 204);
    }

}