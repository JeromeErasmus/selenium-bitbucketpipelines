<?php
/**
 * Created by PhpStorm.
 * User: jeremy.paul
 * Date: 17/12/2015
 * Time: 1:51 PM
 */

namespace App\Controllers;


use App\Config\Config;
use Elf\Event\RestEvent;
use App\Models\LogTypes;
/**
 * Class AppRestController
 * @package App\Controllers
 * Extends the rest controller by adding logging functionality
 */
class AppRestController extends RestEvent{

    public $constants;
    private $jobId;
    /**
     *
     */
    public function init()
    {
        $this->view = "JsonView";
        $this->constants = $this->app->config->get('httpLogConstants');
    }


    /**
     * @param array $params
     * @return null
     * @throws \Exception
     */
    public function default_event($params = array())
    {
        $methodName = 'handle' . ucfirst($this->app->request()->getHttpMethod());
        $httpMethod = $this->app->request()->getHttpMethod();
        if(method_exists($this, $methodName)) {
            if ($httpMethod != 'GET') {
                if($httpMethod == 'POST') {
                    $userInput = $this->app->request()->retrieveJSONInput();
                    if(array_key_exists('jobId',$userInput)) {
                        $this->jobId = $userInput['jobId'];
//                        Assume that if a controller extends AppRestController, refId refers to the job id
                    } elseif (array_key_exists('refId',$userInput)) {
                        $this->jobId = $userInput['refId'];
                    }
                } else {
                    $entity = $this->handleGET($this->app->request());
                    if(array_key_exists('jobId',$entity)) {
                        $this->jobId = $entity['jobId'];
//                        Assume that if a controller extends AppRestController, refId refers to the job id
                    } elseif (array_key_exists('refId',$entity)) {
                        $this->jobId = $entity['refId'];
                    }
                }

                $data = $this->$methodName($this->app->request());

                $scriptExecutable = $this->processLogData();
                if(!empty($scriptExecutable)){
//              This works at running background scripts
                    exec($scriptExecutable . "  2>&1 ", $output);
                }
            } else {
                $data = $this->$methodName($this->app->request());
            }
            $this->set('data', $data);

            return;

        } else {
            throw new \Exception('Not Implemented');
        }
    }

    public function processLogData()
    {
        $logData = array();
        $scriptExecutable = array();

        $statusCode = $this->get('status_code');
        $locationUrl = $this->get('locationUrl');

        $request = $this->app->request();
        $constants = $this->app->config->get('httpLogConstants');
        $requestType = $request->getHttpMethod();
        $capsule = $this->app->service('eloquent')->getCapsule();
        if (in_array($requestType, $constants['methodsToBeLogged']) && in_array($statusCode, $constants['successfulHttpCodes'])) {

            $assocType = $request->getRoute();
            $logTypes = LogTypes::where('route_name','like' ,$assocType['controller'])->get();

            foreach ($logTypes as $log) {
                $logData['assocType'] = $log->id;
            }

            if ($statusCode == 201) {
                $locationUrl = explode('/', $locationUrl);
                $logData['assocId'] = end($locationUrl);
                // Additional information for Job Alerts so that the timeline can display who the alert was intended to be towards
                if (strcasecmp($assocType['controller'],'JobAlert') == 0) {
                    $input = $this->app->request()->retrieveJSONInput();
                    $userDetails = $this->app->service('User')->retrieveUserDetails($input['alertDestinationUserId']);
                    $logData['additionalInformation'] = array( 'alert' => array(
                                                                    'Attention' => $userDetails['userFirstName'] . '_' . $userDetails['userLastName'],
                                                                    'Comment'   => str_replace(' ','_',$input['alertMessage']),
                                                                ),
                                                        );
                }
            }

            $logData['request'] = $request->getRequestUri();
            $logData['jobId'] = $this->jobId;

            if ($requestType == 'DELETE' || $requestType == 'PATCH') {
                $logData['assocId'] = $request->query('id');
            }

            $logData['actionType'] = $constants['actionTypes'][$requestType];

            $logData['createdBy'] = $this->app->service('user')->getCurrentUser()->getUserSysid();
            $now = new \DateTime();
            $logData['dateAndTime'] = $now->format('Y-m-d.H:i:s');

            $location = $this->app->config->defaults['paths']['services'] . DIRECTORY_SEPARATOR;
            $location = str_replace('\\', '/', $location);

            $env = getenv('ENVIRONMENT'); 
            if($env == "staging"){
                $php_location = "\"C:".DIRECTORY_SEPARATOR."Program Files (x86)".DIRECTORY_SEPARATOR."PHP".DIRECTORY_SEPARATOR."v5.6".DIRECTORY_SEPARATOR."php-cgi.exe\" ";
            }else{
                $php_location = "php ";
            }
                        
            $scriptExecutable = $php_location . ASYNC_SCRIPT." LogRequest "
                                                    ."--ENVIRONMENT=\"$env\" ". "--logData=\"" . (json_encode($logData)) . "\"";
        }

        return $scriptExecutable;
    }


    public function requestAsync($scriptExecutable){
        if (substr(php_uname(), 0, 7) == "Windows") {
            //This works at running background scripts
            return pclose(popen("start /B " . $scriptExecutable. " > nul", "r"));  // logging is done in the actual script
        } else {
            return exec($scriptExecutable . " > /dev/null &");
        }
    }

}