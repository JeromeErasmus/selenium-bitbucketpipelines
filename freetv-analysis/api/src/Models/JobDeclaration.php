<?php

namespace App\Models;
use App\Models\EloquentModel;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Description of TvcFormats
 *
 * @author adam
 */
class JobDeclaration extends EloquentModel
{
    /**
     * override the default primary key
     */
    protected $primaryKey = "jde_job_id";
    use SoftDeletes;
    protected $table = 'job_declarations';
    public $timestamps = true;

    protected $declarationId;

    /**
     * this is required for soft deleting
     * @var type
     */
    protected $dates = ['updated_at', 'created_at', 'deleted_at'];

    protected $fieldMap = [
        'jobId' => [
            'name' => 'jde_job_id',
            'rules' => 'required',
        ],
        'scheduledInChildrenPrograms' => [
            'name' => 'jde_scheduled_in_children_programs',
            'rules' => 'boolean',
        ],
        'meetsAudioLevelsAndLoudness' => [
            'name' => 'jde_meets_audio_levels_and_loudness',
            'rules' => 'boolean',
        ],
        'auNzContent' => [
            'name' => 'jde_au_nz_content',
            'rules' => 'boolean',
        ],
        'auNzProduced' => [
            'name' => 'jde_au_nz_produced',
            'rules' => 'boolean',
        ],
        'auNzProducer' => [
            'name' => 'jde_au_nz_producer',
            'rules' => 'boolean',
        ],
        'auNzPrincipal' => [
            'name' => 'jde_au_nz_principal',
            'rules' => 'boolean',
        ],
        'auNzPhotographyDirector' => [
            'name' => 'jde_au_nz_photography_director',
            'rules' => 'boolean',
        ],
        'auNzWriters' => [
            'name' => 'jde_au_nz_writers',
            'rules' => 'boolean',
        ],
        'auNzVisualEditing' => [
            'name' => 'jde_au_nz_visual_editing',
            'rules' => 'boolean',
        ],
        'auNzSoundtrackproduction' => [
            'name' => 'jde_au_nz_soundtrackproduction',
            'rules' => 'boolean',
        ],
        'auNzRoleFilled' => [
            'name' => 'jde_au_nz_role_filled',
            'rules' => 'boolean',
        ],
        'auNzAllPerformances' => [
            'name' => 'jde_au_nz_all_performances',
            'rules' => 'boolean',
        ],
        'auNzComposedMusic' => [
            'name' => 'jde_au_nz_composed_music',
            'rules' => 'boolean',
        ],
        'auNzAnimation' => [
            'name' => 'jde_au_nz_animation',
            'rules' => 'boolean',
        ],
        'promoteTherapeuticGoods' => [
            'name' => 'jde_promote_therapeutic_goods',
            'rules' => 'boolean',
        ],
        'asmiApproved' => [
            'name' => 'jde_asmi_approved',
            'rules' => 'boolean',
        ],
        'asmiApprovalnumber' => [
            'name' => 'jde_asmi_approvalnumber',
            'rules' => 'boolean',
        ],
        'forCharity' => [
            'name' => 'jde_for_charity',
            'rules' => 'boolean',
        ],
        'cTime' => [
            'name' => 'jde_c_time',
            'rules' => 'boolean'
        ],
        'providedCharityProof' => [
            'name' => 'jde_provided_charity_proof',
            'rules' => 'boolean',
        ],
        'advertiserAbn' => [
            'name' => 'jde_advertiser_abn',
            'rules' => 'string',
        ],
        'auNzExempt' => [
            'name' => 'jde_au_nz_exempt',
            'rules' => 'boolean',
        ],
    ];


}




