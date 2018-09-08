<?php

namespace App\Models;

use App\Models\EloquentModel;
use Elf\Exception\NotFoundException;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Description of TvcFormats
 *
 * @author adam
 */
class Comment extends EloquentModel
{
    
    use SoftDeletes;

    /**
     * override the default primary key
     */
    protected $primaryKey = "id";
    protected $table = 'comment';
    public $timestamps = true;
    
    /**
     * this is required for soft deleting
     * @var type 
     */
    protected $dates = ['updated_at', 'created_at', 'deleted_at'];
    
    protected $hidden = ['deleted_at', 'type', 'reply_type'];
    
    protected $fieldMap = [
        'id' => [
            'name' => 'id',
        ],
        'comment' => [
            'name' => 'comment',
            'rules' => 'required|string',
        ],
        'createdAt' => [
            'name' => 'created_at',
        ],
        'createdBy' => [
            'name' => 'created_by',
            'rules' => 'numeric',
        ],
        'updatedAt' => [
            'name' => 'updated_at',
        ],
        'parent' => [
            'name' => 'parent',
            'rules' => 'numeric',
        ],
        'replyType' => [
            'name' => 'reply_type',
            'rules' => 'numeric',
        ],
        'refId' => [
            'name' => 'ref_id',
            'rules' => 'numeric|required',
        ],
        'type' => [
            'name' => 'type',
            'rules' => 'numeric|required',
        ],
    ];
    
    
    public function commentType()
    {
        return $this->hasOne('App\\Models\\CommentType', 'id', 'type');
    }
    
    public function commentReplyType()
    {
        return $this->hasOne('App\\Models\\CommentReplyType', 'id', 'reply_type');
    }
    
    public function replies()
    {
        return $this->hasMany('App\\Models\\Comment', 'parent', 'id')->with('commentType','commentReplyType')->orderBy('created_at', 'desc');
    }
    
    public function toRestful() {
        $data = $this->camelKeys(parent::toArray());
        if (empty($data['parent']) && empty($data['replies'])) {
            $data['notRepliedTo'] = true;
        }
        $data['createdBy'] = $this->app->service('User')->retrieveUserDetails($data['createdBy']);
        try {
            unset($data['createdBy']['userPermissionSet']);
            if(isset($data['replies']) && !empty($data['replies'])) {
                foreach($data['replies'] as $index => $reply) {
                    if(isset($reply['createdBy']) && !empty($reply['createdBy'])) {
                        $data['replies'][$index]['createdBy'] = $this->app->service('User')->retrieveUserDetails($reply['createdBy']);
                        unset($data['replies'][$index]['createdBy']['userPermissionSet']);
                    }
                }
            }
            return $data;
        } catch(\Exception $exception) {
            
        }
        return $data;
        
    }
    
}
