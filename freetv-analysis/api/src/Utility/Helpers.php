<?php
/**
 * Created by PhpStorm.
 * User: thimira.gunasekera
 * Date: 9/09/2015
 * Time: 3:01 PM
 */

namespace App\Utility;


class Helpers {

    /**
     *
     * Converts a single variable that should be a boolean into a boolean
     */
    static public function convertVariableToBool($val)
    {
        if ($val === "true" || $val === true || $val === "1" || $val === 1) {
            $val = true;
        } else if ($val === "false" || $val === false || $val === "0" || $val === 0 || $val === NULL) {
            $val = false;
        }
        return $val;
    }

    /*
     *
     * Converts an array that contains values that should be booleans into booleans
     */

    static public function convertToBool($array)
    {
        foreach($array as $key => &$val) {
            if ($val === "true" || $val === true || $val === "1" || $val === 1) {
                $val = true;
            } else if ($val === "false" || $val === false || $val === "0" || $val === 0 || $val === NULL) {
                $val = false;
            }
        }
        return $array;
    }
    
    public static function convertToCamelCase($str = "", $delimiter = "_", $capitalizeFirstCharacter = false)
    {
        $str = str_replace(' ', '', ucwords(str_replace($delimiter, ' ', $str)));

        if (!$capitalizeFirstCharacter) {
            $str[0] = strtolower($str[0]);
        }

        return $str;
    }
    
    public static function convertToNull($value)
    {

        if(is_null($value) || $value === "") {
            return null;
        }
        return strtolower($value) === "null" ? null : $value;
    }

}