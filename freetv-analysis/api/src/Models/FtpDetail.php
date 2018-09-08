<?php

namespace App\Models;

use App\Models\EloquentModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class FtpDetail extends EloquentModel
{

    use SoftDeletes;

    /**
     * override the default primary key
     */
    protected $primaryKey = "id";
    protected $table = 'ftp_detail';
    public $timestamps = false;


    protected $dates = ['deleted_at'];

    protected $fieldMap = [
        'id' => [
            'name' => "id",
        ],
        'url' => [
            'name' => 'url',
            'rules' => 'max:50|required',
        ],
        'username' => [
            'name' => 'username',
            'rules' => 'max:50|required',
        ],
        'password' => [
            'name' => 'password',
            'rules' => 'max:50|required',
        ],
        'sftp' => [
            'name' => 'sftp',
            'rules' => 'boolean|required',
        ],
        'initialPath' => [
            'name' => 'initial_path',
            'rules' => 'max:50|required',
        ],
    ];
}