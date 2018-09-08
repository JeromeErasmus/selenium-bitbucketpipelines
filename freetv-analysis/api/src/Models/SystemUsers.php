<?php
/**
 * Created by PhpStorm.
 * User: thimira.gunasekera
 * Date: 4/05/2016
 * Time: 1:52 PM
 */

namespace App\Models;

use Elf\Db\AbstractAction;

class SystemUsers extends AbstractAction {


    /**
     * Gets the type of user (users,agency users, network users)
     *
     * @param $sysId
     * @return mixed
     * @throws \Exception
     */
    public function getUserType($sysId) {

        if (!is_numeric($sysId)) {
            throw new \Exception("sysid has to be numeric");
        }

        $sql = "SELECT sys_user_type FROM system_users WHERE sysid = :sysid";

        $data = $this->fetchOneAssoc($sql, [':sysid' => $sysId]);

        if (empty($data)) {
            throw new \Exception("Cannot find user in system user table");
        }

        return $data['sys_user_type'];

    }


    public function load()
    {

    }

    public function save(){}
}