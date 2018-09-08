<?php
/**
 * Created by PhpStorm.
 * User: Adam
 */

namespace App\Models;

use App\Models\EloquentModel;

/**
 *
 *
 * @author adam
 */
class RequirementsTemplate extends EloquentModel
{
    
    
    /**
     * override the default primary key
     */
    protected $primaryKey = "rma_id";
    protected $table = 'requirements_templates';
    public $timestamps = false;

    protected $fieldMap = [
        'id' => [
            'name' => 'rma_id'  
        ],
        'code' => [
            'name' => "rma_code",
        ],
        'requirementCategoryId' => [
            'name' => 'rma_category_id',
        ],
        'shortDescription' => [
            'name' => 'rma_short_description',
        ],
        'description' => [
            'name' => 'rma_agency_description',
        ],
        'active' => [
            'name' => 'rma_active',
            'type' => 'boolean',
        ],
        'stationComment' => [
            'name' => 'rma_network_description',
        ],
        'visibleCadAdviceSlip' => [
            'name' => 'rma_show_on_cad_slip',
            'type' => 'boolean'
        ],
        'visibleDailyReport' => [
            'name' => 'rma_show_on_daily_report',
            'type' => 'boolean'
        ],
        'mandatory' => [
            'name' => 'rma_mandatory',
            'type' => 'boolean'
        ]
    ];
}