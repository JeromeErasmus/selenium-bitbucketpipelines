<?php
/**
 * Created by PhpStorm.
 * User: thimira.gunasekera
 * Date: 8/09/2015
 * Time: 2:31 PM
 */

namespace App\Models;
use Elf\Exception\ConflictException;
use Elf\Exception\NotFoundException;
use Elf\Utility\Convert;

class Advertiser extends \Elf\Db\AbstractAction
{

    private $advertiserId;
    private $advertiserCode;
    private $advertiserName;
    private $defaultCategory;
    private $abn;
    private $allowLateSubmissions;
    private $authorisedCharity;
    private $charityLastChecked;
    private $cadNotes;
    private $accountNotes;
    private $approved;
    private $active;

    public function __construct($app) {
        parent::__construct($app);
        return $this; // for method chaining
    }

    protected $fieldMap = array (
        'advertiserId' => array('name' => 'adv_id'),
        'advertiserCode' => array('name' => 'adv_code'),
        'advertiserName' => array('name' => 'adv_name'),
        'defaultCategory' => array('name' => 'adv_default_advertiser_category'),
        'abn' => array('name' => 'adv_abn'),
        'allowLateSubmissions' => array('name' => 'adv_allow_late_submission'),
        'authorisedCharity' => array('name' => 'adv_is_charity'),
        'charityLastChecked' => array('name' => 'adv_charity_last_checked'),
        'cadNotes' => array('name' => 'adv_cad_notes'),
        'accountNotes' => array('name' => 'adv_acc_notes'),
        'active' => array('name' => 'adv_is_active'),
        'approved' => array('name' => 'adv_is_approved'),
    );

    public function load()
    {
        if (empty($this->advertiserId) ) {
            throw new \Exception("No advertise Id present.");
        }

        $sql = "SELECT
         adv_code,
         adv_name,
         adv_default_advertiser_category,
         adv_abn,
         adv_allow_late_submission,
         adv_is_charity,
         adv_charity_last_checked,
         adv_cad_notes,
         adv_acc_notes,
         adv_is_active,
         adv_is_approved
         FROM
         dbo.advertisers
         WHERE
         adv_id = :adv_id
         ";

        $advertiser = $this->fetchOneAssoc($sql, array(':adv_id' => $this->advertiserId));

        if (empty($advertiser)) {
            throw new NotFoundException("No Advertiser found with ID " . $this->advertiserId);
        }

        $this->advertiserCode = $advertiser['adv_code'];
        $this->advertiserName = $advertiser['adv_name'];
        $this->defaultCategory = $advertiser['adv_default_advertiser_category'];
        $this->abn = $advertiser['adv_abn'];
        $this->allowLateSubmissions = Convert::toBoolean($advertiser['adv_allow_late_submission']);
        $this->authorisedCharity = Convert::toBoolean($advertiser['adv_is_charity']);
        $this->charityLastChecked = $advertiser['adv_charity_last_checked'];
        $this->cadNotes = $advertiser['adv_cad_notes'];
        $this->accountNotes = $advertiser['adv_acc_notes'];
        $this->active = Convert::toBoolean($advertiser['adv_is_active']);
        $this->approved = Convert::toBoolean($advertiser['adv_is_approved']);

    }

    public function findByAdvertiserId($advertiserId) {
        $params = array(
            ':adv_id' => $advertiserId,
        );

        $sql = "SELECT adv_id FROM dbo.advertisers where advertisers.adv_id = :adv_id";

        $data = $this->fetchOneAssoc($sql, $params);
        if (!$data) {
            throw new NotFoundException("Cannot Find advertiser with id of $advertiserId");
        }

        $role = new Advertiser($this->app);
        $role->setAdvertiserId($data['adv_id']);
        $role->load();
        return $role;
    }

    public function findDuplicates($advertiserId,$advertiserName) {

        $sql = "SELECT
                        adv.adv_id AS advertiserId,
                        adv.adv_code AS advertiserCode,
                        adv.adv_name as advertiserName,
                        adv.adv_abn as abn
                    FROM
                        advertisers adv ";

        if(!empty($advertiserId)) {
            $params = array(
                ':src_adv_id' => $advertiserId,
                ':adv_id' => $advertiserId,
            );

            $sql .= " WHERE
                        SOUNDEX(adv.adv_name)
                          =
                            SOUNDEX(
                              (SELECT
                                adv_name
                               FROM advertisers adv
                               WHERE adv.adv_id = :adv_id
                               )
                            ) AND adv.adv_id <> :src_adv_id
                    ";

            $data = $this->fetchAllAssoc($sql, $params);
            if (empty($data)) {
                throw new NotFoundException("Cannot Find advertiser with id of $advertiserId");
            }

            return $data;
        }

        if(!empty($advertiserName)) {
            $params = array(
                ':advertiserName' => '%' . $advertiserName . '%',
            );
            // Not Soundex as soundex produces unexpected results, i.e. searching for the word 'Flowers',
            // one would expect 1300 Flowers to turn up. It does not. Ergo 'Like' search is used
            $sql .= " WHERE
                        adv.adv_name
                        LIKE :advertiserName
                      AND
                        adv.adv_is_active = 1
                    ";
            $data = $this->fetchAllAssoc($sql, $params);

            return $data;
        }

    }

    
    public function setAdvertiserId($id)
    {
        $this->advertiserId = $id;
    }

    public function getAsArray()
    {
        $ret = array();
        foreach ($this->fieldMap as $key => $val) {
            $ret[$key] = $this->$key;
        }
        return $ret;
    }

    public function getById($id)
    {
        $advertiserMdl = new Advertiser($this->app);
        $advertiser = $advertiserMdl->findByAdvertiserId($id);
        return $advertiser->getAsArray();
    }

    public function save()
    {
        if(null == $this->advertiserId) { // new record so create it
            return $this->createRecord();
        } else { // existing record so update it
            $this->updateRecord();
        }
    }

    public function createRecord()
    {
        $sql = "SELECT adv_code FROM dbo.advertisers WHERE adv_code = :adv_code";

        $records = $this->fetchOneAssoc($sql, array(':adv_code' => $this->advertiserCode));

        if ( $records !== false ) {     //a key already exists, fail gracefully
            throw new ConflictException("Advertiser code already exists.");
        }


        $sql = "INSERT INTO dbo.advertisers(
              adv_code,
              adv_name,
              adv_default_advertiser_category,
              adv_abn,
              adv_allow_late_submission,
              adv_is_charity,
              adv_charity_last_checked,
              adv_cad_notes,
              adv_acc_notes,
              adv_is_approved,
              adv_is_active
          )VALUES(
              :adv_code,
              :adv_name,
              :adv_default_advertiser_category,
              :adv_abn,
              :adv_allow_late_submission,
              :adv_is_charity,
              :adv_charity_last_checked,
              :adv_cad_notes,
              :adv_acc_notes,
              :adv_is_approved,
              :adv_is_active
          )";

        $params = array(
            ':adv_code' => $this->advertiserCode,
            ':adv_name' => $this->advertiserName,
            ':adv_default_advertiser_category' => $this->defaultCategory,
            ':adv_abn' => $this->abn,
            ':adv_allow_late_submission' => Convert::toBoolean($this->allowLateSubmissions),
            ':adv_is_charity' => Convert::toBoolean($this->authorisedCharity),
            ':adv_charity_last_checked' => $this->charityLastChecked,
            ':adv_cad_notes' => $this->cadNotes,
            ':adv_acc_notes' => $this->accountNotes,
            ':adv_is_approved' => Convert::toBoolean($this->approved),
            ':adv_is_active' => Convert::toBoolean($this->active),
        );
       
        $id = $this->insert($sql, $params);
        
        if (empty($id)) {
            throw new \Exception("Couldn't insert advertiser.");
        }
        $this->advertiserId = $id;
        return $id;
    }

    public function updateRecord()
    {
        $sql = "UPDATE dbo.advertisers SET
          adv_code = :adv_code,
          adv_name = :adv_name,
          adv_default_advertiser_category = :adv_default_advertiser_category,
          adv_abn = :adv_abn,
          adv_allow_late_submission = :adv_allow_late_submission,
          adv_is_charity = :adv_is_charity,
          adv_charity_last_checked = :adv_charity_last_checked,
          adv_cad_notes = :adv_cad_notes,
          adv_acc_notes = :adv_acc_notes,
          adv_is_approved = :adv_is_approved,
          adv_is_active = :adv_is_active
          WHERE
          adv_id = :adv_id";

        $params = array(
            ':adv_id' => $this->advertiserId,
            ':adv_code' => $this->advertiserCode,
            ':adv_name' => $this->advertiserName,
            ':adv_default_advertiser_category' => $this->defaultCategory,
            ':adv_abn' => $this->abn,
            ':adv_allow_late_submission' => Convert::toBoolean($this->allowLateSubmissions),
            ':adv_is_charity' => Convert::toBoolean($this->authorisedCharity),
            ':adv_charity_last_checked' => $this->charityLastChecked,
            ':adv_cad_notes' => $this->cadNotes,
            ':adv_acc_notes' => $this->accountNotes,
            ':adv_is_approved' => Convert::toBoolean($this->approved),
            ':adv_is_active' => Convert::toBoolean($this->active),
        );

        return $this->update($sql, $params);
    }

    public function deleteRecord()
    {
        if (empty($this->advertiserId)) {
            throw new \Exception("No ID given.");
        }

        $sql = "SELECT COUNT(*) as COUNT FROM dbo.advertisers WHERE adv_id = :adv_id";
        $result = $this->fetchOneAssoc($sql, array(':adv_id' => $this->advertiserId));

        if ($result["COUNT"] != 1) {
            return false;
        }

        $sql = "DELETE dbo.advertisers WHERE adv_id = :adv_id";

        return $this->delete($sql, array(':adv_id' => $this->advertiserId));
    }

    public function setFields($params)
    {
        foreach ($params as $key => $val) {
            if (array_key_exists($key, $this->fieldMap)) {
                $this->$key = $val;
            }
        }
    }



}