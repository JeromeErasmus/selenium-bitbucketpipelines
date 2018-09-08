<?php

namespace App\Models;

use App\Models\EloquentModel;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Description of TvcFormats
 *
 * @author adam
 */
class DocumentUploadType extends EloquentModel
{

    /**
     * override the default primary key
     */
    protected $primaryKey = "jum_material_type_id";
    protected $table = 'job_uploaded_material_types';
    public $timestamps = false;
    

    protected $fieldMap = [
        'jobUploadTypeId' => [
            'name' => 'jum_material_type_id',
        ],
        'jobUploadTypeName' => [
            'name' => 'jum_material_type_name',
            'rules' => 'required|string',
        ],
    ];
}
