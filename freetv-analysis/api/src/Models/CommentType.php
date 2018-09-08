<?php

namespace App\Models;

use App\Models\EloquentModel;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Description of TvcFormats
 *
 * @author adam
 */
class CommentType extends EloquentModel
{
    
    use SoftDeletes;

    /**
     * override the default primary key
     */
    protected $primaryKey = "id";
    protected $table = 'comment_type';
    public $timestamps = true;
    
    /**
     * this is required for soft deleting
     * @var type 
     */
    protected $dates = ['updated_at', 'created_at', 'deleted_at'];
    
    
    protected $hidden = ['deleted_at'];
    

    protected $fieldMap = [
        'id' => [
            'name' => 'id',
        ],
        'name' => [
            'name' => 'name',
            'rules' => 'required|string',
        ],
        'createdBy' => [
            'name' => 'created_by',
            'rules' => 'numeric',
        ],
        'createdAt' => [
            'name' => 'created_at',
        ],
        'updatedAt' => [
            'name' => 'updated_at',
        ],
    ];
}
