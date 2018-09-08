<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace App\Controllers;


use Elf\Http\Request;
use Elf\Event\RestEvent;
use App\Models\MyMetaModel;


class AgencyUser extends RestEvent
{

    public function handleGet(Request $request)
    {

        $agencyId = $request->query('agencyId');

        if(null === $request->query('userId') && null === $request->query('email'))
        {
            $this->set('status_code', 200);
            return $this->app->collection('agencyuserlist')->getAllAgencyusers($agencyId);
        }

        if ($email = $request->query('email')){
            $agency_user = $this->app->model('AgencyUser');
            $agencyUserModel = $agency_user->findOneByEmail($email);
            $agencyUserId = $agencyUserModel->getUserId();

            $value = $agencyUserModel->getAgencyUserById($agencyUserId);

            $this->set('status_code', 200);
            return($value);
        }

        if ($id = $request->query('userId') ){
            $agency_user = $this->app->model('AgencyUser');

            if ($value = $agency_user->getAgencyUserById($id)) {
                $this->set('status_code', 200);
                return $value ;
            } else {
                throw new \Exception("Couldn't fetch agency user.");
            }

        }
    }

    public function handlePost(Request $request)
    {
        $agency_user = $this->app->model('AgencyUser');

        $agency_user_post_data = $request->retrieveJSONInput();

        if (isset($agency_user_post_data['isActive'])) {
            $agency_user_post_data['isActive'] = \Elf\Utility\Convert::toBoolean($agency_user_post_data['isActive']);
        }

        if (isset($agency_user_post_data['isAgencyAdmin'])) {
            $agency_user_post_data['isAgencyAdmin'] = \Elf\Utility\Convert::toBoolean($agency_user_post_data['isAgencyAdmin']);
        }

        if (isset($agency_user_post_data['agencyId'])){
            $agency_user_post_data['agencyId'] = (int) $agency_user_post_data['agencyId'];
        }

        //We should never hit a case where we will want to link agencies at time of creation
//        if(isset($agency_user_post_data['agenciesToLink']) && !empty($agency_user_post_data['agenciesToLink'])) {
//            $agency_user_post_data['agencyId'] = null;
//        }

        if($agency_user->validate($agency_user_post_data)) {
            $clientId = $request->query('clientId');

            if($id = $agency_user->createAgencyUser($agency_user_post_data)) {
                $url = "/agencyUser/clientId/$clientId/userId/$id";

//                if(isset($agency_user_post_data['agenciesToLink']) && !empty($agency_user_post_data['agenciesToLink'])) {
//                    $agency_user->linkAgencies($agency_user_post_data['agenciesToLink'], $id);
//                    unset($agency_user_post_data['agenciesToLink']);
//                }

                $this->set('locationUrl', $url);
                $this->set('status_code', 201);
            } else {
                throw new \Exception("Couldn't create agency user.");
            }
        } else {
            $this->set('status_code', 400);
            return $agency_user->getErrors();
        }

    }

    public function handlePatch(Request $request)
    {
        $id = $request->query('userId');

        $agency_user = $this->app->model('AgencyUser');

        if($id){
            $data = $request->retrieveJSONInput();

            if(isset($data['agenciesToLink']) && !empty($data['agenciesToLink'])) {
                $agency_user->linkAgencies($data['agenciesToLink'],$id);
                $agency_user_data = $agency_user->getAgencyUserById($id);
                //if the agenciesToLink is the same as agu_ag_id in the agency user table then 
                //the user is turning into a freelancer. So set agencyId to null.
                if($agency_user_data['agency']['agencyId'] == $data['agenciesToLink']){
                    $data['agencyId'] = null;
                }
                unset($data['agenciesToLink']);
            }
            if($agency_user->modifyAgencyUser($id,$data)){
                $this->set('status_code',204);
            } else {
                throw new \Exception("Failed to update record");
            }
        } else {
            throw new \Exception("No Id.");
        }
    }

    public function handleDelete(Request $request)
    {
        $id = $request->query('userId');
        if($id) {
            $agency_user = $this->app->model('AgencyUser');

            if ($agency_user->deleteAgencyUser($id)) {
                $this->set('status_code',204);
            } else {
                throw new \Exception("Failed to delete");
            }
            return;
        }
        throw new \Exception("No User Id Specified to delete");

    }
}
