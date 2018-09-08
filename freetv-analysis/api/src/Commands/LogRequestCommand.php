<?php
/**
 * Created by PhpStorm.
 * User: jeremy.paul
 * Date: 17/12/2015
 * Time: 2:03 PM
 */
namespace App\Commands;

use App\Models\AuditLog;
use Elf\Core\Module;
use App\Services\Eloquent;
use Symfony\Component\Yaml\Yaml;
use App\Models\JobAuditLog;

class LogRequestCommand extends Module{

    private $args;

    public function __construct($app)
    {
        $this->app = $app;
    }

    public function execute($arguments)
    {
        if(!empty($arguments)) {
            $logData = (Yaml::parse($arguments['logData']));
            $logData['dateAndTime'] = str_replace('.',' ',$logData['dateAndTime']);
            if(!empty($logData['additionalInformation'])) {
                $logData['additionalInformation'] = json_encode($logData['additionalInformation']);
            }
            $this->args = $logData;
            $this->logRequest();
        }
    }
    public function logRequest()
    {
        $auditLog = new AuditLog();
        $jobAuditLog = new JobAuditLog();
        $auditLog->setFromArray($this->args);

        $eloquentInstance = new Eloquent($this->app);
        $capsule = $eloquentInstance->getCapsule();
        if ($auditLog->validate()) {
            $auditLog->save();
            $this->args['auditLogId'] = $auditLog->id;
            $jobAuditLog->setFromArray($this->args);
            if ($jobAuditLog->validate()) {
                $jobAuditLog->save();
            }
            return;
        }
    }
}