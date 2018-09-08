<?php

namespace App\Models;

use App\Models\EloquentModel;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Description of TvcFormats
 *
 * @author adam
 */
class Country extends EloquentModel
{
    
    use SoftDeletes;
    
    /**
     * override the default primary key
     */
    protected $primaryKey = "cty_id";
    protected $table = 'countries';
    public $timestamps = false;
    
    /**
     * this is required for soft deleting
     * @var type 
     */
    protected $dates = ['deleted_at'];

    protected $fieldMap = [
        'id' => [
            'name' => "cty_id",
        ],
        'name' => [
            'name' => 'cty_name',
            'rules' => 'max:50|required',
        ],
        'internalName' => [
            'name' => 'cty_internal_name',
            'rules' => 'max:50|required',
        ],
        'code' => [
            'name' => 'cty_code',
            'rules' => 'max:10|required',
        ],  
    ];

     /**
      * getCountryNameFromCountryId
      *
      * @param [int] $countryId
      * @return void
      */
    public static function getCountryNameFromCountryId($countryId)
    {
        $country = Country::where('cty_id', '=',  $countryId)->get()->toArray();

        if(!empty($country) && !empty($country[0]['cty_name'])) {
            
            return $country[0]['cty_name'];

        }
    }
    
}