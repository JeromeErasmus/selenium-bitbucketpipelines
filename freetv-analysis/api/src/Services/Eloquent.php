<?php

namespace App\Services;

use Illuminate\Database\Capsule\Manager as Capsule;
use Elf\Core\Module;

/**
 * Eloquent
 * @author adam
 */
class Eloquent extends Module {
    //put your code here
    
    protected $configKey = 'database';
    
    /**
     * initialize configuration for the Eloquent capsule
     */
    public function init()
    {
        $this->config['driver'] = $this->config['type']; // capsule requires a driver param which is the PDO driver type
        $this->config['host'] = $this->config['server']; // capsule requires a host param which is the PDO server
    }
    
    /**
     * instantiate a new capsule with the application db configs
     * @return Capsule
     */
    public function getCapsule()
    {
        $capsule = new Capsule;        
        $capsule->addConnection($this->config);
        $capsule->bootEloquent();
        return $capsule;
    }
    
}
