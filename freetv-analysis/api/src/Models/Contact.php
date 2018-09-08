<?php

namespace App\Models;

use App\Models\EloquentModel;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Description of Contact
 *
 * @author Jeremy
 */
class Contact extends EloquentModel
{
    
    use SoftDeletes;

    /**
     * override the default primary key
     */
    protected $primaryKey = 'id';
    protected $table = 'contact';
    public $timestamps = true;
    
    /**
     * this is required for soft deleting
     * @var type 
     */
    protected $dates = ['updated_at', 'created_at', 'deleted_at'];
    
    protected $hidden = ['deleted_at', 'contactable_type'];
    
    protected $fieldMap = [
        'id' => [
            'name' => 'id',
        ],
        'name' => [
            'name' => 'name',
            'rules' => 'required|string|max:512',
        ],
        'email' => [
            'name' => 'email',
            'rules' => 'required|string|max:512',
        ],
        'notificationType' => [
            'name' => 'notification_type',
            'rules' => 'numeric|required',
        ],
        'active' => [
            'name' => 'active',
            'rules' => 'boolean',
        ],
        'createdAt' => [
            'name' => 'created_at',
            'rules' => 'date',
        ],
        'updatedAt' => [
            'name' => 'updated_at',
            'rules' => 'date',
        ],
        'contactableId' => [
            'name' => 'contactable_id',
            'rules' => 'numeric|required',
        ],
        'contactableType' => [
            'name' => 'contactable_type',
            'rules' => 'required|string|max:512',
        ],
        'createdBy' => [
            'name' => 'created_by',
            'rules' => 'required|string|max:512',
        ],
    ];
    
    public function contactable()
    {
        return $this->morphTo();
    }

    public function notifications()
    {
        return $this->hasOne('App\\Models\\Notification','id','notification_type');
    }

}
