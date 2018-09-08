<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Elf\Exception\ForbiddenException as AccessDeniedException;
use Elf\Exception\NotFoundException;
use Elf\Exception\NotAcceptableException;
use App\Utility\Helpers;
use Elf\Utility\Convert;

/**
 * Description of EloquentModel
 *
 * @author adam
 */
class EloquentModel extends Model {

    use \Elf\Db\BladeRunner;

    protected $fieldMap = [];

    /**
     * @var array Related entities to get
     */
    public static $entities = [];

    /**
     * @var array Fields which are not allowed to be updated
     */
    protected $notToBeUpdated = [];

    /**
     * @var array Errors from validation
     */
    public $errors = [];

    /**
     * @var array Validation rules
     */
    public $rules = [];

    public function getDateFormat()
    {
        return 'Y-m-d H:i:s.u';
    }

    /**
     * Convert a DateTime to a storable string.
     * SQL Server will not accept 6 digit second fragment (PHP default: see getDateFormat Y-m-d H:i:s.u)
     * trim three digits off the value returned from the parent.
     *
     * @param  \DateTime|int  $value
     * @return string
     */

    public function fromDateTime($value)
    {
        return substr(parent::fromDateTime($value), 0, -3);
    }

    public static $snakeAttributes = false;

    /**
     * assemble/set the Eloquent rules based on the field map property
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->getRules();
        parent::__construct($attributes);
    }
    
    public function setApp($app)
    {
        $this->app = $app;
    }

    /**
     * Boot the model
     */
    public static function boot()
    {
        parent::boot();

        // Not allow certain fields to be updated
        static::updating(function(EloquentModel $model) {

            $primaryKey = $model->getKeyName();

            $originalPrimaryKey = $model->getOriginal($primaryKey);
            $newPrimaryKey = $model->getAttribute($primaryKey);

            if ($originalPrimaryKey != $newPrimaryKey) {
                throw new AccessDeniedException('Updating primary keys not allowed');
            }

            // Only do this if the model has "not to be updated" fields
            if(! empty($model->notToBeUpdated)) {
                // Get all fields
                foreach ($model->getAttributes() as $attributeKey => $attributeVal) {
                    // Check if they have changed
                    if ($model->getOriginal($attributeKey) != $model->getAttribute($attributeKey)) {
                        //check the array with fields that are not allowed to be updated
                        if(in_array($attributeKey, $model->notToBeUpdated)) {
                            throw new AccessDeniedException('Nice try buddy! You can not update the ' . $attributeKey);
                        }
                    }
                }

            }
        });

    }

    /**
     * backwards compatibility with old model interface
     * @return type
     */
    public function getAsArray($camelCase = true)
    {
        $data = array();
        foreach($this->fieldMap as $field => $map) {
            $fieldName = $camelCase ? $field : $map['name'];
            if((isset($map['expose']) &&
                false !== $map['expose']) || 
                !isset($map['expose'])) {
                    $data[$fieldName] = isset($this->attributes[$map['name']]) ? $this->attributes[$map['name']] : null;
            }

            if (isset($map['type']) && $map['type'] == 'boolean') {
                $data[$fieldName] = Convert::toBoolean($this->attributes[$map['name']]);
            }
        }
        return $data;
    }

    
    /**
     * backwards compatibility with old model interface
     * @param type $attributes
     * @return type
     */
    public function setFromArray($attributes)
    { 
        foreach ($this->fieldMap as $key => $mapping) { //loop through the field map and only pick up data that we want 
            if(isset($attributes[$key])) {
                $this->$key = $attributes[$key];
                if(isset($mapping['type']) && $mapping['type'] === 'boolean') {
                    $this->attributes[$mapping['name']] = Convert::toBoolean($attributes[$key]);
                } else {
                    $this->attributes[$mapping['name']] = $attributes[$key];
                }
                
            }  
        }
    }
    
    public function toRestful() {
        return $this->camelKeys(parent::toArray());
    }

    public static function arrayToRestful($eloquentCollection) {
        $data = array();
        foreach($eloquentCollection as $individualResult) {
            $data[] = $individualResult->toRestful();
        }
        return $data;
    }
    
    public function camelKeys(array $data) { 
        $return = [];
        foreach($data as $key => $datum) {
            if(is_array($datum)) {
                $return[Helpers::convertToCamelCase($key)] = $this->camelKeys($datum);
            } else {
                $return[Helpers::convertToCamelCase($key)] = $datum;
            }
        }
        return $return;
    }
    

    /**
     * validated the array with eloquent validation
     * @return boolean
     */
    public function validate()
    {

        //Assume we have the attributes as set
        try{
            $this->scriptBlockChecker_r($this->attributes, true);
        }catch(NotAcceptableException $e){
            throw new NotAcceptableException("Script blocks are not acceptable");
        }

        $validation = new ValidationManager('en', __DIR__);
        $validator = $validation->getValidator();
        $v = $validator->make($this->attributes, $this->rules);

        if($v->fails()) {
            $this->errors = $v->errors();
            return false;
        }
        
        return true;
    }

    
    /**
     * try to set with a setter
     * then try to set property based on the field map (camel case conersion)
     * otherwise just set it
     * @param type $name
     * @param type $value
     * @return type
     */
    public function __set($name, $value)
    {

        $setter =  'set' . ucfirst($name);
        if(method_exists($this, $setter)) {
            $this->$setter($value);
            return;
        }
        
        if(isset($this->fieldMap[$name])) {
            $map = $this->fieldMap[$name];
            $this->{$map['name']} = $value;
            return;
        }
        
        $this->$name = $value;
    }
    
    /**
     * try to get with a getter
     * then try to get based on field map
     * then just get or return null
     * @param type $name
     * @return type
     */
    public function __get($name)
    {
        $getter =  'get' . ucfirst($name);
        if(method_exists($this, $getter)) {
            return $this->$getter();
        }
        
        if(isset($this->fieldMap[$name])) {
            $map = $this->fieldMap[$name];
            return $this->attributes[$map['name']];
        }
        
        return $this->getAttribute($name);

    }
    
    /**
     * assemle the Eloquent rules from the field map property.
     */
    private function getRules()
    {
        foreach($this->fieldMap as $field => $map) {
            if(isset($map['rules'])) {
                if($map['rules'] == 'afterCurrentDate'){
                    $this->rules[$map['name']] = 'after:'.date("Y-m-d H:i:s");
                }else{
                    $this->rules[$map['name']] = $map['rules'];
                }
            }
        }
    }

    /**
     * Function to add additional rules or to add an ignore rule for an id
     *
     * @param array $params add the id as array('owner_id' => 11)
     * @param array $merge extra rules
     * @return array
     * @throws \Exception
     */
    public function rules ($params=[], $merge=[]) {

        //get the rules using late static binding with the keyword static http://php.net/manual/en/language.oop5.late-static-bindings.php
        $mergedRules = array_merge(static::$rules, $merge);

        foreach($params as $paramkey => $param) {
            if(! isset($mergedRules[$paramkey])) {
                throw new \Exception('The key does not exist in the rules');
            }

            $mergedRules[$paramkey] = preg_replace('/'.$paramkey.'/', $paramkey.','.$param.','.$paramkey, $mergedRules[$paramkey]);
        }

        // Remove rules which have been nulled by the merged rules
        foreach ($merge as $key => $value) {
            if ($value == null && array_key_exists($key, $mergedRules)) {
                unset($mergedRules[$key]);
            }
        }

        return $mergedRules;
    }

    /**
     * @param mixed $id
     * @param array $columns
     * @return \Illuminate\Support\Collection|null|static
     * @throws \Exception
     */
    public static function findOrFail($id, $columns = array('*'))
    {
        if ( ! is_null($model = static::find($id, $columns))) return $model;

        throw new NotFoundException(get_class(static::getModel()) . ' not found');
    }

    /**
     * Retrieves all records, including related entities
     *
     * @param array $columns
     * @return \Illuminate\Database\Eloquent\Collection|static[]/j #
     */
    public static function all($columns = array('*'))
    {
        if(static::$entities) {
            //if relations are set then use these in a with statement for eager loading
            return self::with(static::$entities)->get();
        }
        return parent::all($columns);
    }

    /**
     * Overwrite the parents function to create a model first and get the data from the model
     * This makes sure other keys add will be ignored and only the model specific fields are being saved
     * @param array $attributes
     * @return static
     */
    public static function firstOrCreate(array $attributes)
    {
        return parent::firstOrCreate(with(new static($attributes))->attributes);
    }

    /**
     * Create a date range query
     * It will return a query with dateField >= mindate and dateField <= maxDate
     * @param $query
     * @param $dateField
     * @param $minDate
     * @param $maxDate
     * @return mixed
     * @throws \Exception
     */
    public function scopeDateRange($query, $dateField, $minDate, $maxDate)
    {
        if(empty($dateField)) {
            throw new \Exception('Please supply a datefield');
        }
        if(empty($minDate) || empty($maxDate)) {
            throw new \Exception('For a daterange you need to supply a mindate and a maxdate');
        }

        return $query->where($dateField, '>=', $minDate)->where($dateField, '<=', $maxDate);
    }

    /**
     * Create a datetime range query
     * It will return a query with dateField >= minDateTime and dateField < maxDateTime
     * @param $query
     * @param $dateField
     * @param $minDateTime
     * @param $maxDateTime
     * @return mixed
     * @throws \Exception
     */
    public function scopeDateTimeRange($query, $dateField, $minDateTime, $maxDateTime)
    {
        if(empty($dateField)) {
            throw new \Exception('Please supply a datefield');
        }
        if(empty($minDateTime) || empty($maxDateTime)) {
            throw new \Exception('For a daterange you need to supply a mindate and a maxdate');
        }

        return $query->where($dateField, '>=', $minDateTime)->where($dateField, '<=', $maxDateTime);
    }

    public function convertUsingFieldMap($originalData, $camelCase = true)
    {
        $data = array();
        foreach($this->fieldMap as $field => $map) {
            $fieldName = $camelCase ? $field : $map['name'];
            if((isset($map['expose']) &&
                    false !== $map['expose']) ||
                !isset($map['expose'])) {
                $data[$fieldName] = isset($originalData[$map['name']]) ? $originalData[$map['name']] : null;
            }

            if (isset($map['type']) && $map['type'] == 'boolean') {
                $data[$fieldName] = isset($originalData[$map['name']]) ? Convert::toBoolean($originalData[$map['name']]) : null;
            }
        }
        return $data;
    }

    /**
     * This function takes in params and returns an array of filters to use when retrieving an Eloquent collection
     * @param $params
     * @return array
     */

    public function retrieveFilterArray($params)
    {
        $filters = array();
        foreach($this->fieldMap as $field => $map) {
            if(isset($params[$field])) {
                $filters[$map['name']] = $params[$field];
            }
        }

        return $filters;
    }

}
