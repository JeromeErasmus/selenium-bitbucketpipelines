<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Controllers;


use App\Models\Contact;
use App\Services\EmailInterface;
use Elf\Exception\NotFoundException;
use Elf\Exception\MalformedException;
use Elf\Http\Request;
use Elf\Event\RestEvent;
use App\Models\MyMetaModel;
use App\Models\JobDeclaration;

/**
 * Description of Job
 *
 * @author michael
 */
class OasDashboard extends AppRestController
{

    /**
     *
     * @param Request $request
     * @return type
     */
    public function handleGet(Request $request)
    {
        $dashboardType = $request->query('dashboardType');
        $agencyId = $request->query('agencyId');
        $searchQuery = $request->query('q');

        $oasDashboardModel = $this->app->model('OasDashboard');
        $oasDashboardModel->setAgencyId($agencyId);
        $oasDashboardModel->setSearchQuery($searchQuery);

        if (empty($dashboardType)) {
            $jobs = $oasDashboardModel->getAllDashboardJobs();
            return $jobs;
        }

        $oasDashboardModel->setOasDashboardType($dashboardType);
        $jobs[$dashboardType] = $oasDashboardModel->getDashboardJobs();

        return $jobs;

    }
}
