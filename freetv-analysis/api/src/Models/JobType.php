<?php
/**
 * Created by PhpStorm.
 * User: thimira.gunasekera
 * Date: 22/09/2015
 * Time: 2:40 PM
 */

namespace App\Models;


use Elf\Db\AbstractAction;

class JobType extends AbstractAction
{
    private $jobTypeId;
    private $jobTypeName;

    public function load()
    {
        $sql = "SELECT jty_name FROM job_types WHERE jty_id = :id";
        $data = $this->fetchOneAssoc($sql, [':id' => $this->jobTypeId]);
        $this->jobTypeName = $data['jty_name'];
    }

    public function save()
    {

    }

    public function getById($id)
    {
        $this->jobTypeId = $id;
        $this->load();

        return ['jobTypeId' => $this->jobTypeId, 'jobTypeName' => $this->jobTypeName];
    }
}