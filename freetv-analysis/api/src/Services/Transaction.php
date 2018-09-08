<?php

namespace App\Services;

use Elf\Db\AbstractAction;
use Elf\Exception\NotFoundException;

class Transaction extends AbstractAction {
    
    public function load()
    {
        // TODO: Implement load() method.
    }

    public function save()
    {
        // TODO: Implement save() method.
    }

    /**
     * @param $arraySQL
     * @throws \Exception
     */
    public function executeTransaction($arraySQL)
    {
        try{
            $this->app->db->beginTransaction();
            foreach($arraySQL as $sql){
                $this->execute($sql);
            }
            $this->app->db->commit();
        }
        catch(\Exception $e){
            $this->app->db->rollback();
            echo "Failed: " . $e->getMessage();
        }
    }

    public function beginTransaction(){
        $this->app->db->beginTransaction();
    }

    public function endTransaction(){
        $this->app->db->commit();
    }

    public function rollbackTransaction(){
        $this->app->db->rollback();
    }


}