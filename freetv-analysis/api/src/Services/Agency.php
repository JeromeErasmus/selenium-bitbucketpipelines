<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Services;

use Elf\Db\AbstractParent;
use Elf\Exception\ConflictException;
use Elf\Exception\NotFoundException;

/**
 * Description of Agency
 *
 * @author michael
 */
class Agency extends AbstractParent  {
    //put your code here

    public function removeStopCredit($agencyId)
    {
        $sql = "UPDATE [dbo].[agencies] SET ag_scr_id = NULL where ag_id = :ag_id";
        $params = [':ag_id' => $agencyId];

        $sth = $this->app->db->prepare($sql);
        $sth->execute($params);

        $error = $sth->errorInfo();
        if ($error[0] != '00000') {
            throw new \Exception(print_r($error,1));
        }

        return true;

    }

    public function merge($sourceAgencyId, $targetAgencyId)
    {

        $params = [
            ":src_ag_id" => $sourceAgencyId,
            ":trg_ag_id" => $targetAgencyId
        ];
//        Commented out till we have a new server that supports transactions in a way we understand
//        At the moment the BEGIN TRANSACTION kills the first update query even if it's valid
//        $sql  = "BEGIN TRANSACTION; ";
        $sql =  "UPDATE [dbo].[agency_users] SET agu_ag_id = :trg_ag_id WHERE agu_ag_id = :src_ag_id";

        $sth = $this->app->db->prepare($sql);
        $sth->execute($params);

        $sql = "UPDATE [dbo].[agency_agency_user] SET aau_ag_id = :trg_ag_id WHERE aau_ag_id = :src_ag_id;";

        $sth = $this->app->db->prepare($sql);
        $sth->execute($params);

        $sql = "UPDATE [dbo].[contact] SET contactable_id = :trg_ag_id WHERE contactable_type = 'App\\Models\\Agencies' AND contactable_id = :src_ag_id;";

        $sth = $this->app->db->prepare($sql);
        $sth->execute($params);

        $sql = "UPDATE [dbo].[network_users] SET agency_id = :trg_ag_id WHERE agency_id = :src_ag_id;";

        $sth = $this->app->db->prepare($sql);
        $sth->execute($params);

        $sql = "UPDATE [dbo].[jobs] SET job_ag_id = :trg_ag_id WHERE job_ag_id = :src_ag_id;";

        $sth = $this->app->db->prepare($sql);
        $sth->execute($params);

        unset($params[':trg_ag_id']);

        $sql  = "DELETE FROM [dbo].[agencies] WHERE ag_id = :src_ag_id; ";
        $sql .= "COMMIT TRANSACTION; ";

        $sth = $this->app->db->prepare($sql);
        $sth->execute($params);

        $error = $sth->errorInfo();
        if ($error[0] != '00000') {
            throw new \Exception(print_r($error,1));
        }

        return true;

    }

    public function linkAgencyUser($email, $agencyId) {

        $sql = "SELECT agu_id FROM agency_users WHERE agu_email_address = :email";

        $result = $this->fetchOneAssoc($sql, [':email' => $email]);

        if (empty($result['agu_id'])) {
            throw new NotFoundException("No user with email specified");
        }

        $aguId = $result['agu_id'];

        $sql = "SELECT COUNT(*) as count FROM agency_agency_user WHERE aau_ag_id = :agId AND aau_agu_id = :aguId";

        $result = $this->fetchOneAssoc($sql, [':agId' => $agencyId, ':aguId' => $aguId]);

        if ($result['count'] > 0) {
            throw new ConflictException("Agency User already linked");
        }

        $sql = "INSERT INTO dbo.agency_agency_user(aau_ag_id,aau_agu_id) VALUES(:agId, :aguId)";

        $this->insert($sql, [':agId' => $agencyId, ':aguId' => $aguId]);


    }

    /**
     * @param $agencyId
     * @param $inputData
     * @return bool
     */
    public function isKFIDataUpdated($agencyId, $inputData) {

        // retrieving the old data
        $agencyModelOnOldData = $this->app->model('agency');
        $agencyModelOnOldData->setAgencyId($agencyId);
        $agencyModelOnOldData->load();

        // if these fields are changed
        if ($inputData['address1'] != $agencyModelOnOldData->getAddress1()
            || $inputData['address2'] != $agencyModelOnOldData->getAddress2()
            || $inputData['city'] != $agencyModelOnOldData->getCity()
            || $inputData['state'] != $agencyModelOnOldData->getState()
            || $inputData['postCode'] != $agencyModelOnOldData->getPostCode()
            || $inputData['creditLimit'] != $agencyModelOnOldData->getCreditLimit()) {

            return true;

        } else {

            return false;
        }

    }
}
