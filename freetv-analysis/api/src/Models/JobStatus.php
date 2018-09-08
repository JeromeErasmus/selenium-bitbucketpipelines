<?php
/**
 * Created by PhpStorm.
 * User: thimira.gunasekera
 * Date: 22/09/2015
 * Time: 2:41 PM
 */

namespace App\Models;


use Elf\Db\AbstractAction;

class JobStatus extends AbstractAction
{
    private $jobStatusId;
    private $jobStatusName;

    public function load()
    {
        $sql = "SELECT jst_name FROM job_statuses WHERE jst_id = :id";
        $data = $this->fetchOneAssoc($sql, [':id' => $this->jobStatusId]);
        $this->jobStatusName = $data['jst_name'];
    }

    public function save()
    {

    }

    public function getById($id)
    {
        $this->jobStatusId = $id;
        $this->load();

        return ['jobStatusId' => $this->jobStatusId, 'jobStatusName' => $this->jobStatusName];
    }
}