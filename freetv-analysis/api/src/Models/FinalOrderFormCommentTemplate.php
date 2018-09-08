<?php
/**
 * Created by PhpStorm.
 * User: shirleen.sharma
 * Date: 29/10/15
 * Time: 13:58
 */

namespace App\Models;

use App\Models\EloquentModel;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Description of TvcFormats
 *
 * @author adam
 */
class FinalOrderFormCommentTemplate extends EloquentModel
{

    /**
     * override the default primary key
     */
    protected $primaryKey = "id";
    protected $table = 'final_order_form_comment_template';
    public $timestamps = false;

    protected $fieldMap = [
        'id' => [
            'name' => "id",
        ],
        'name' => [
            'name' => 'name',
        ],
        'comment' => [
            'name' => 'comment',
        ],
        'active' => [
            'name' => 'active',
        ],
    ];

}