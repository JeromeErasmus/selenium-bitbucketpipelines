<?php
/**
 * Created by PhpStorm.
 * User: Adam
 */

namespace App\Models;

use App\Models\EloquentModel;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Description of TvcFormats
 *
 * @author adam
 */
class RequirementCategory extends EloquentModel
{
    use SoftDeletes;
    /**
     * override the default primary key
     */
    protected $primaryKey = "id";
    protected $table = 'requirement_category';
    public $timestamps = true;

    protected $fieldMap = [
        'id' => [
            'name' => "id",
        ],
        'name' => [
            'name' => 'name',
        ],
        'active' => [
            'name' => 'active',
            'type' => 'boolean',
        ],
    ];
}