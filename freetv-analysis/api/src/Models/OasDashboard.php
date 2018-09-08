<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Models;
use Elf\Application\Application;
use Elf\Db\AbstractAction;
use Elf\Exception\NotFoundException;
use App\Collections\KeyNumberList;
use App\Models\KeyNumber;
use App\Models\OrderForm;
use Illuminate\Database\Capsule\Manager;
use Illuminate\Support\Facades\DB;

class OasDashboard extends AbstractAction {

    private $oasDashboardType;
    private $agencyId;
    private $searchQuery;
    private $params = [];
    // Flag to trigger getting requirements nested within key numbers
    private $getRequirements = false;

    private $oasDashboardTypes = [
        'drafts',
        'action_required',
        'applications',
        'classified_and_closed',
        'pre_checks',
    ];

    public function save()
    {

    }

    public function load()
    {

    }

    /**
     * For each of the job types defined above, return an array containing all the segregated jobs
     * @return array
     */
    public function getAllDashboardJobs()
    {
        $jobs = array();

        foreach ($this->oasDashboardTypes as $type) {
            $this->setOasDashboardType($type);
            $jobs[$type] = $this->getDashboardJobs();
        }

        return $jobs;
    }

    public function getDashboardJobs()
    {

        $method = 'get_' . strtolower($this->oasDashboardType) . '_conditions';

        if(!method_exists($this,$method)) {
            return array();
        }

        $sql = "
            
            {$this->preSearchConditions()}

            SELECT DISTINCT TOP 250 
            jobs.job_id,
            jobs.job_reference_no,
            jobs.job_title,
            jobs.job_submission_date,
            adv.adv_name,
            jst.jst_name,
            jty.jty_name
            FROM jobs
            LEFT JOIN advertisers adv
            ON 
            jobs.job_adv_id = adv.adv_id
            LEFT JOIN job_statuses jst
            ON 
            jobs.job_jst_id = jst.jst_id 
            LEFT JOIN job_types jty
            ON 
            jobs.job_jty_id = jty.jty_id
            
            {$this->searchJoinConditions()}
            
            WHERE 
            1=1
            
            {$this->get_agency_conditions()}
                        
            {$this->addSearchConditions()}
            
            {$this->$method()}
            
            ORDER BY jobs.job_id DESC
            ";

        $data = $this->fetchAllAssoc($sql,$this->params);

        if (empty($data)) {
            return array();
        }

        foreach ($data as $key => $job) {
            $job['keyNumbers'] = $this->getKeyNumbers($job['job_id']);
            $data[$key] = $job;
        }

        return $data;
    }

    public function searchJoinConditions()
    {
        if (empty($this->searchQuery)) {
            return '';
        }
        return '
            
            LEFT JOIN dbo.tvcs AS t ON jobs.job_id = t.tvc_job_id
            LEFT JOIN dbo.agencies AS ag ON jobs.job_ag_id = ag.ag_id
            
        
        ';

    }

    public function preSearchConditions()
    {
        if (empty($this->searchQuery)) {
            return '';
        }
        return '
        
            DECLARE @search varchar(50)
            SET @search = :query
        
        ';
    }

    public function addSearchConditions()
    {
        if (empty($this->searchQuery)) {
            return '';
        }

        $sql = "
            AND
            (
                t.tvc_cad_no LIKE '%' + @search + '%' OR
                t.tvc_key_no LIKE '%' + @search + '%' OR
                jobs.job_reference_no LIKE '%' + @search + '%' OR
                jobs.job_purchase_order LIKE '%' + @search + '%' OR
                jobs.job_title LIKE '%' + @search + '%' OR
                adv.adv_name LIKE '%' + @search + '%' OR
                t.tvc_product_description LIKE '%' + @search + '%'
            )
            
        ";


        $this->params[':query'] = $this->searchQuery;

        return $sql;
    }

    public function get_action_required_conditions()
    {
        $sql = "
            AND
            jst.jst_name = 'Awaiting Agency Feedback'
            AND 
            jobs.deleted_at IS NULL
            AND 
            jobs.job_submission_date IS NOT NULL
            
        ";

        return $sql;
    }

    public function get_agency_conditions()
    {
        if (!$this->getAgencyId()) {
            return '';
        }
        $sql = "
        
            AND 
            jobs.job_ag_id = :agencyId
            
        ";

        $this->params[':agencyId'] = $this->agencyId;

        return $sql;

    }

    public function get_drafts_conditions()
    {
        $sql = "
             
            AND
            jobs.job_submission_date IS NULL
            AND 
            jobs.deleted_at IS NULL
            
        ";

        return $sql;
    }

    public function get_applications_conditions()
    {
        $sql = "
             
            AND
            jst.jst_id NOT IN (4,5) 
            AND
            jty.jty_name != 'Pre-check'
            AND 
            jobs.deleted_at IS NULL
            
        ";

        return $sql;
    }

    public function get_pre_checks_conditions()
    {
        $sql = "
            
            AND
            jst.jst_id NOT IN (4,5) 
            AND
            jty.jty_name = 'Pre-check'
            AND 
            jobs.deleted_at IS NULL
            
        ";

        return $sql;
    }

    public function get_network_conditions()
    {
        $this->setGetRequirements(true);

        $sql = "
            
            AND
            jty.jty_name != 'Pre-check'
            AND
            jobs.job_submission_date IS NOT NULL 
            AND 
            jobs.deleted_at IS NULL
            
        ";

        return $sql;
    }

    public function get_classified_and_closed_conditions()
    {
        $sql = "
            
            AND
            jst.jst_id IN (4,5) 
            AND 
            jobs.deleted_at IS NULL
            
        ";

        return $sql;
    }

    public function getKeyNumbers($jobId)
    {
        $collection = $this->app->collection('keynumberlist');

        $collection->setParams(array('jobId' => $jobId));

        $keyNumbers = $collection->getAll($this->getGetRequirements());

        return $keyNumbers;

    }

    /**
     * @return mixed
     */
    public function getOasDashboardType()
    {
        return $this->oasDashboardType;
    }

    /**
     * @param mixed $oasDashboardType
     */
    public function setOasDashboardType($oasDashboardType)
    {
        $this->oasDashboardType = $oasDashboardType;
    }

    /**
     * @return mixed
     */
    public function getAgencyId()
    {
        return $this->agencyId;
    }

    /**
     * @param mixed $agencyId
     */
    public function setAgencyId($agencyId)
    {
        $this->agencyId = $agencyId;
    }

    /**
     * @return mixed
     */
    public function getSearchQuery()
    {
        return $this->searchQuery;
    }

    /**
     * @param mixed $searchQuery
     */
    public function setSearchQuery($searchQuery)
    {
        $this->searchQuery = $searchQuery;
    }

    /**
     * @return mixed
     */
    public function getGetRequirements()
    {
        return $this->getRequirements;
    }

    /**
     * @param mixed $getRequirements
     */
    public function setGetRequirements($getRequirements)
    {
        $this->getRequirements = $getRequirements;
    }



}
