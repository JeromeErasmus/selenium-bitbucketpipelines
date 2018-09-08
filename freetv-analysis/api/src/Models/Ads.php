<?php
/**
 * Created by PhpStorm.
 * User: thimira.gunasekera
 * Date: 4/03/2016
 * Time: 10:48 AM
 */

namespace App\Models;


use Elf\Db\AbstractAction;

class Ads extends AbstractAction
{
    public function load(){}

    public function save(){}

    public function getLastFailedTime()
    {
        $sql = "SELECT TOP 1 timestamp FROM active_directory_failures ORDER BY id DESC";
        $data = $this->fetchOneAssoc($sql);

        if ($data === false) {
            throw new \Exception("Cannot retrieve last active directory failure time");
        }

        return new \DateTime($data['timestamp']);
    }

    /**
     * @return mixed
     * @throws \Exception
     *
     * Insert an ADS failure timestamp into the database
     */
    public function insertFail()
    {
        $sql = "INSERT INTO active_directory_failures(timestamp) VALUES (GETDATE());";

        return $this->insert($sql);
    }

}