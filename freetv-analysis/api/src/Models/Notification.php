<?php

namespace App\Models;

use App\Models\EloquentModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class Notification extends EloquentModel{
    use SoftDeletes;

    /**
     * override the default primary key
     */
    protected $primaryKey = 'id';
    protected $table = 'notification';
    public $timestamps = true;

    /**
     * this is required for soft deleting
     * @var type
     */
    protected $dates = ['updated_at', 'created_at', 'deleted_at'];

    protected $hidden = ['deleted_at'];

    protected $fieldMap = [
        "id" => [
            "name" => "id",
        ],
        "name" => [
            "name" => "name",
        ],
        "createdAt" => [
            "name" => "created_at",
            'rules' => 'date',
        ],
        "updatedAt" => [
            "name" => "updated_at",
            'rules' => 'date',
        ],
        "createdBy" => [
            "name" => "created_by",
            'rules' => 'numeric',
        ],
    ];
}