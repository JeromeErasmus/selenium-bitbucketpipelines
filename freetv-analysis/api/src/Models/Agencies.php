<?php
/**
 * Created by PhpStorm.
 * User: jeremy.paul
 * Date: 13/10/2015
 * Time: 10:45 AM
 */

namespace App\Models;

use App\Models\EloquentModel;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;


class Agencies extends EloquentModel
{

    public $timestamps = false;
    public $table = 'agencies';
    protected $primaryKey = 'ag_id';
   // public $contacts = [];
    
 
    protected $fieldMap = [
        'id' => [
            'name' => 'id',
        ]
    ];

   public function contacts()
   {
       return $this->morphMany('App\\Models\\Contact', 'contactable');
   }
   
}