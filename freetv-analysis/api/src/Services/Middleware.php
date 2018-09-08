<?php
/**
 * Created by PhpStorm.
 * User: thimira.gunasekera
 * Date: 2/05/2016
 * Time: 2:42 PM
 */

namespace App\Services;


use App\Services\Permissions\Permissions;
use Elf\Core\Module;

class Middleware extends Module
{

    private $permissions;

    public function addPermission(Permissions $permission) {
        $this->permissions = $permission;
    }

    public function handlePermissions($route, $event, $app) {

        if (empty($this->permissions)) {
            throw new \Exception("No permission validator loaded");
        }

        $this->permissions->handlePermission($route, $event, $app);

    }

}