<?php
/**
 * Created by PhpStorm.
 * User: jeremy.paul
 * Date: 7/09/2015
 * Time: 5:09 PM
 */

namespace App\Models;
use Elf\Exception\ConflictException;
use Elf\Exception\NotFoundException;
use Elf\Utility\Convert;
use App\Models\Agencies as Model;
use Illuminate\Database\Eloquent\Collection;


class Agency extends \Elf\Db\AbstractAction {

    private $agencyId;
    private $agencyCode;
    private $agencyName;
    private $billingSubGroup;
    private $agencyApproved;

    private $primaryContactName;
    private $primaryContactEmail;
    private $primaryContactNotificationId;

    private $address1;
    private $address2;
    private $city;
    private $state;
    private $country;
    private $postCode;
    private $phoneNumber;
    private $faxNumber;
    private $mobileNumber;
    private $abn;
    private $accountsContact;
    private $accountsEmailAddress;
    private $accountsPhoneNumber;
    private $billingCode;
    private $creditLimit;
    private $accountType;
    private $isSyncUpdate;
    private $purchaseOrder;
    private $addTaxable;
    private $overseasGSTStatusApproved;
    private $active;
    private $networkId;

    private $lastApplicationDate;
    private $lastSubmittedJobId;

    private $agencyGroup;
    private $agencyGroupId;
    private $agencyTableGroupId;

    private $agencyGroupName;

    private $stopCreditId;
    private $stopCreditTableId;
    private $stopCreditNumber;
    private $stopCreditReason;
    private $stopCredit;
    private $jobCount;

    private $secondaryContactName;
    private $secondaryContactEmail;
    private $secondaryContactNotificationId;

    private $primaryWestpacToken;

    private $agencies_tablename = 'dbo.agencies';
    
    protected $fieldMap = array (
        "agencyId" => array (
            'name' => 'ag_id',
            'type' => 'numeric',
            'required' => false,
            'allowEmpty' => true
        ),
        "agencyCode" => array (
            'name' => 'ag_code',
            'type' => 'string',
            'required' => true,
            'allowEmpty' => false
        ),
        "agencyName" => array (
            'name' => 'ag_name',
            'type' => 'string',
            'required' => true,
            'allowEmpty' => false
        ),
        "billingSubGroup" => array (
            'name' => 'ag_billing_sub_group',
            'type' => 'string',
            'required' => false,
            'allowEmpty' => true
        ),
        "agencyApproved" => array (
            'name' => 'ag_is_approved',
            'type' => 'boolean',
            'required' => false,
            'allowEmpty' => true
        ),
        "primaryContactName" => array (
            'name' => 'ag_contact_name',
            'type' => 'string',
            'required' => false,
            'allowEmpty' => true
        ),
        "primaryContactEmail" => array (
            'name' => 'ag_primary_contact_email',
            'type' => 'string',
            'required' => false,
            'allowEmpty' => true
        ),
        "primaryContactNotificationId" => array (
            'name' => 'ag_primary_contact_notification_id',
            'type' => 'string',
            'required' => false,
            'allowEmpty' => true
        ),
        'secondaryContactName' => array (
            'name' => 'ag_secondary_contact_name',
            'type' => 'string',
            'required' => false,
            'allowEmpty' => true
        ),
        'secondaryContactEmail' => array (
            'name' => 'ag_secondary_contact_email',
            'type' => 'string',
            'required' => false,
            'allowEmpty' => true
        ),
        'secondaryContactNotificationId' => array (
            'name' => 'ag_secondary_contact_notification_id',
            'type' => 'string',
            'required' => false,
            'allowEmpty' => true
        ),
        "address1" => array (
            'name' => 'ag_address1',
            'type' => 'string',
            'required' => false,
            'allowEmpty' => true
        ),
        "address2" => array (
            'name' => 'ag_address2',
            'type' => 'string',
            'required' => false,
            'allowEmpty' => true
        ),
        "city" => array (
            'name' => 'ag_city',
            'type' => 'string',
            'required' => false,
            'allowEmpty' => true
        ),
        "state" => array (
            'name' => 'ag_sta_id',
            'type' => 'string',
            'required' => false,
            'allowEmpty' => true,
        ),
        "country" => array (
            'name' => 'ag_country',
            'type' => 'numeric',
            'required' => false,
            'allowEmpty' => true
        ),
        "postCode" => array (
            'name' => 'ag_postcode',
            'type' => 'string',
            'required' => false,
            'allowEmpty' => true
        ),
        "phoneNumber" => array (
            'name' => 'ag_phone',
            'type' => 'string',
            'required' => false,
            'allowEmpty' => true
        ),
        "faxNumber" => array (
            'name' => 'ag_fax',
            'type' => 'string',
            'required' => false,
            'allowEmpty' => true
        ),
        "mobileNumber" => array (
            'name' => 'ag_mobile',
            'type' => 'string',
            'required' => false,
            'allowEmpty' => true
        ),
        "abn" => array (
            'name' => 'ag_abn',
            'type' => 'string',
            'required' => false,
            'allowEmpty' => true
        ),
        "networkId" => array (
            'name' => 'ag_net_id',
            'type' => 'string',
            'required' => false,
            'allowEmpty' => true
        ),
        "accountsContact" => array (
            'name' => 'ag_accounts_contact',
            'type' => 'string',
            'required' => false,
            'allowEmpty' => true
        ),
        "accountsEmailAddress" => array (
            'name' => 'ag_accounts_email',
            'type' => 'string',
            'required' => false,
            'allowEmpty' => true
        ),
        "accountsPhoneNumber" => array (
            'name' => 'ag_accounts_phone_number',
            'type' => 'string',
            'required' => false,
            'allowEmpty' => true
        ),
        "billingCode" => array (
            'name' => 'ag_billing_code',
            'type' => 'string',
            'required' => false,
            'allowEmpty' => true
        ),
        "creditLimit" => array (
            'name' => 'ag_credit_limit',
            'type' => 'string',
            'required' => false,
            'allowEmpty' => true
        ),
        "accountType" => array (
            'name' => 'ag_account_type',
            'type' => 'string',
            'required' => false,
            'allowEmpty' => true
        ),
        "isSyncUpdate" => array (
            'name' => 'ag_is_sync_update',
            'type' => 'string',
            'required' => false,
            'allowEmpty' => true
        ),
        "purchaseOrder" => array (
            'name' => 'ag_purchase_order_required',
            'type' => 'string',
            'required' => false,
            'allowEmpty' => false
        ),
        "addTaxable" => array (
            'name' => 'ag_add_taxable',
            'type' => 'boolean',
            'required' => false,
            'allowEmpty' => true
        ),
        "overseasGSTStatusApproved" => array (
            'name' => 'ag_overseas_gst',
            'type' => 'boolean',
            'required' => false,
            'allowEmpty' => true
        ),
        "active" => array (
            'name' => 'ag_is_active',
            'type' => 'boolean',
            'required' => '',
            'allowEmpty' => ''
        ),
        "lastApplicationDate" => array (
            'name' => 'lastApplicationDate',
            'type' => 'string',
            'required' => false,
            'allowEmpty' => true
        ),
        "agencyGroup" => array (
            'name' => 'agencyGroup',
            'type' => 'numeric',
            'required' => false,
            'allowEmpty' => true
        ),
        "stopCredit" => array (
            'name' => 'stopCredit',
            'type' => 'numeric',
            'required' => false,
            'allowEmpty' => true
        ),
        "agencyTableGroupId" => array (
            'name' => 'agr_id',
            'type' => 'string',
            'required' => false,
            'allowEmpty' => true
        ),
        "agencyGroupId" => array (
            'name' => 'ag_agr_id',
            'type' => 'numeric',
            'required' => false,
            'allowEmpty' => true
        ),
        "agencyGroupName" => array (
            'name' => 'agr_name',
            'type' => 'string',
            'required' => false,
            'allowEmpty' => true
        ),
        "stopCreditTableId" => array (
            'name' => 'scr_id',
            'type' => 'numeric',
            'required' => false,
            'allowEmpty' => true
        ),
        "stopCreditId" => array (
            'name' => 'ag_scr_id',
            'type' => 'numeric',
            'required' => false,
            'allowEmpty' => true
        ),
        "stopCreditNumber" => array (
            'name' => 'scr_number',
            'type' => 'string',
            'required' => false,
            'allowEmpty' => true
        ),
        "stopCreditReason" => array (
            'name' => 'scr_reason',
            'type' => 'string',
            'required' => false,
            'allowEmpty' => true
        ),
        "lastSubmittedJobId" => array (
            'name' => 'job_id',
            'type' => 'numeric',
            'required' => false,
            'allowEmpty' => true
        ),
        'jobCount' => array (
            'name' => 'job_count',
            'type' => 'string',
            'required' => false,
            'allowEmpty' => true
        ),
        'primaryWestpacToken' => array (
            'name' => 'ag_primary_token_id',
            'type' => 'numeric',
            'required' => false,
            'allowEmpty' => true
        )
    );

    public function __construct($app) {
        parent::__construct($app);
        $this->config = $app->config->get('security');
        return $this; // for method chaining
    }

    public function getAgencyById($id)
    {
        $this->setAgencyId($id);
        $this->load();
    }

    /**
     * Sets the agencyStopCredit when the transaction has failed,
     * This is a specific function only used in WestpacProcessing
     */
    public function setAgencyStopCreditFailedTransaction()
    {
        //update the ag_scr_id
        //OR
        //update the ag_failed_transaction_id
        //if this is selected, patches to westpac tokens that are setting a primary token
        //will need to unset this
        if (!$this->getAgencyId()) {
            throw new \Exception("Invalid ID or Name");
        }
        if ($this->getAgencyId()) {
            $params =   array(
                ':ag_id' => $this->getAgencyId(),
                ':ag_scr_id' => $this->app->config->get('stopCreditReasons')['westpacTransactionFailed']
            );

            $sql = "UPDATE agencies SET ag_scr_id = :ag_scr_id WHERE ag_id = :ag_id";

            return $this->execute($sql,$params);
           
        }
        throw new \Exception("Agency not found");

    }

    public function load()
    {
        if (!$this->getAgencyId()) {
            throw new \Exception("Invalid ID or Name");
        }
        if ($this->getAgencyId()) {
            $params =   array(
                ':agencyId' => $this->getAgencyId(),
            );
            $sql = 'SELECT agency_table.*,job_table.lastApplicationDate,job_table.job_id, n1.name as ag_primary_notification, n2.name as ag_secondary_contact_notification FROM
                    (
                        (
                        SELECT
                            agencies.*,
                            agencyGroup.agr_id ,
                            agencyGroup.agr_name ,
                            stopCredits.scr_id ,
                            stopCredits.scr_number ,
                            stopCredits.scr_reason
                        FROM dbo.agencies

                        LEFT JOIN dbo.agency_groups as agencyGroup
                        ON  agencies.ag_agr_id = agencyGroup.agr_id

                        LEFT JOIN dbo.stop_credits as stopCredits
                        ON agencies.ag_scr_id = stopCredits.scr_id
                        WHERE agencies.ag_id= :agencyId
                    ) as agency_table
                    LEFT JOIN
                    (
                        SELECT
                            jobs.job_ag_id AS job_agency_id,
                            MAX(jobs.job_id) AS job_id,
                            MAX(jobs.job_submission_date) AS lastApplicationDate
                        FROM
                            dbo.jobs
                        GROUP BY
                            jobs.job_ag_id
                    ) as job_table
                    ON job_table.job_agency_id = agency_table.ag_id

                    LEFT JOIN notification as n1
                    ON agency_table.ag_primary_contact_notification_id = n1.id
                    LEFT JOIN notification as n2
                    ON agency_table.ag_secondary_contact_notification_id = n2.id

                    )  ORDER BY ag_id' ;
            $data = $this->fetchOneAssoc($sql,$params);

            if ($data === false) {
                throw new NotFoundException("id doesnt exist");
            }

            $this->setFromArray($data);

            $sql = "SELECT COUNT(*) as job_count FROM dbo.jobs where job_ag_id = :agencyId";
            $data = $this->fetchOneAssoc($sql, [':agencyId' => $this->getAgencyId()]);
            $this->setFromArray($data);
            return;
        }
        throw new \Exception("Agency not found");
    }

    public function save()
    {
        if (!$this->getAgencyId()) {
            return $this->create();
        } elseif ($this->getAgencyId()) {
            return $this->updateAgency();
        }
    }

    /*
     * Deletes an agency, either by name or by agency ID
     */
    public function deleteAgency()
    {
        $sql = "DELETE FROM " . $this->agencies_tablename;
        if ($this->getAgencyId()) {
            $sql .= " WHERE ag_id=" . $this->agencyId;
        } else {
            $sql .= " WHERE ag_name LIKE '" . $this->agencyName . "'";
        }
        $rows = $this->delete($sql);

        if ($rows === 0) {
            return false;
        }
        return true;
    }

    /*
     * Create a new agency
     */
    public function create()
    {
        $sql = 'INSERT INTO ' . $this->agencies_tablename . " ";
        $tableColumns = '(';
        $tableVariables = 'VALUES(';
        $params = array();
        foreach ($this->fieldMap as $key => $mapping) {
            $getMethod = "get" . ucfirst($key);
            if (method_exists($this, $getMethod) && isset($this->$key)) {
                $tableColumns .= $mapping['name'] . ',';
                $tableVariables .= "?,";
                $params[] = $this->$key;
            }
        }

        $tableColumns = rtrim($tableColumns, ',') . ') ';
        $tableVariables = rtrim($tableVariables, ',') . ')';

        $sql .= $tableColumns . $tableVariables;
        try {
            $id = $this->insert($sql,$params);
            $this->setAgencyId($id);
            return true;
        } catch(ConflictException $e) {
            throw new ConflictException($e->getMessage());
        }
    }

    /**
     * @return bool
     * Updates the User depending on whether either the agency id or the agency name is set
     */
    public function updateAgency()
    {
        if ($this->getAgencyId()) {
            $sql = 'UPDATE ' . $this->agencies_tablename . ' SET ';
            $sqlEnding = ' WHERE ag_id = :agencyId ';
            $params = array(
                ":agencyId" => $this->agencyId,
            );
            unset($this->agencyId);
        } elseif ($this->getAgencyName()) { // The controller sets the agency name into the agency id since that is empty.
            $sql = 'UPDATE ' . $this->agencies_tablename . ' SET ';
            $sqlEnding = " WHERE ag_name LIKE '" . $this->getAgencyName() . "'";
            unset($this->agencyName);
        }
        $columnsToBeUpdated = '';
        foreach ($this->fieldMap as $key => $mapping) {
            $getMethod = "get" . ucfirst($key);
            if (method_exists($this, $getMethod) && isset($this->$key)) {
                $columnsToBeUpdated .= $mapping['name'] . '= :' . $key . ' , ' ;
                $params[':'.$key]=  $this->$key;
            }
        }
        $columnsToBeUpdated = rtrim($columnsToBeUpdated, ', ');
        $sql .= $columnsToBeUpdated . $sqlEnding;
        // only execute if we have columns to update!
        if(strlen($columnsToBeUpdated) > 0) {
            $this->execute($sql,$params);
        }


        header('Content-Type:application/json');

        //reset the agencyID because we need that for the token stuff
        $this->setAgencyId($params[':agencyId']);

        return true;
    }

    public function checkForDuplicates($checkForPatch = 0) {
        // Check that the patch request is re-inserting the same agency code to begin with
        if ($checkForPatch) {
            $sql = "SELECT ag_code FROM dbo.agencies WHERE ag_id = :agencyId AND ag_code = :agencyCode";

            $agencyCode = $this->fetchOneAssoc($sql, array(':agencyId' => $this->agencyId, ':agencyCode' => $this->agencyCode));

            // If this is not empty, that means an agency code is returned, which means that the agency code is the same
            if(!empty($agencyCode['ag_code'])) {
                return;
            }

        }
        // Then or if it's not a patch, check that the agency code does not already exist
        $sql = "SELECT ag_code FROM dbo.agencies WHERE ag_code = :agencyCode";

        $agencyCode = $this->fetchOneAssoc($sql, array(':agencyCode' => $this->agencyCode));

        if (!empty($agencyCode['ag_code'])) {
            throw new ConflictException(array('displayMessage' => "Agency Code already exists"));
        }

        return;

    }

    ///// All the setters and getters
    public function setFromArray($data)
    {
        foreach ($this->fieldMap as $key => $mapping) { //loop through the field map and only pick up data that we want
            $setMethod = "set" . ucfirst($key);
            if (method_exists($this, $setMethod) && isset($data[$mapping['name']])) { // check if we can actually update this field
                $this->$setMethod($data[$mapping['name']]);
            } else if (method_exists($this, $setMethod) && isset($data[$key])) { // check if we can actually update this field
                $this->$setMethod($data[$key]);
            }
        }
    }

    public function getAsArray($postCheck = null)
    {
        $returnArray = array();
        foreach ($this->fieldMap as $key => $mapping) { //loop through the field map and only pick up data that we want
            if ($postCheck == null) {
                $getMethod = "get" . ucfirst($key);
                if (method_exists($this, $getMethod) &&
                    (isset($mapping['expose']) &&
                        false !== $mapping['expose']) || !isset($mapping['expose'])
                ) { // check if we can actually update this field
                    $returnArray[$key] = $this->$getMethod();
                }
            } else {
                $getMethod = "get" . ucfirst($key);
                if (method_exists($this, $getMethod) &&
                    (isset($mapping['expose']) &&
                        false !== $mapping['expose']) || !isset($mapping['expose']) &&
                    isset($this->$key)
                ) { // check if we can actually update this field
                    $returnArray[$key] = $this->$getMethod();
                }
            }
        }
        // Retrieve all the additional agency contacts for the particular agency
        $capsule = $this->app->service('eloquent')->getCapsule();
        $agency = Model::find($this->getAgencyId());

        $returnArray['contacts'] = [];

        if(!empty($agency)){
            if( $agency->contacts instanceof Collection) {
                foreach($agency->contacts as $contact) {
                    $returnArray['contacts'][] = $contact->toRestful();
                }
            }
        }


        $notificationContacts = ['primaryContactNotification'=>'primaryContactNotificationId', 'secondaryContactNotification'=>'secondaryContactNotificationId'];

        foreach($notificationContacts as $newName => $fieldId) {
            $returnArray[$newName] = [
                'id' => $this->$fieldId,
                'name' => null
            ];

            if(is_numeric($this->$fieldId)) {
                try{
                    $notifications = Notification::findOrFail($this->$fieldId );
                    $returnArray[$newName]['name'] = $notifications->name;
                } catch(NotFoundException $e){
                    //if there's no name for the id, just return the empty id
                }
            }
        }


        //Get rid of redundant values, the above loop stores these values into an array of their own, formatting
        //them nicely for display.
        if ($postCheck == null) {
            unset(
                $returnArray['agencyTableGroupId'],
                $returnArray['agencyGroupName'],
                $returnArray['stopCreditTableId'],
                $returnArray['stopCreditId'],
                $returnArray['stopCreditNumber'],
                $returnArray['stopCreditReason']
            );
            return $returnArray;
        } else {
            unset(
                $returnArray['agencyTableGroupId'],
                $returnArray['agencyGroupName'],
                $returnArray['stopCreditTableId'],
                $returnArray['stopCreditNumber'],
                $returnArray['stopCreditReason'],
                $returnArray['primaryContactNotification']
            );
            return $returnArray;
        }
    }

    public function getById($id)
    {
        $agencyModel = new Agency($this->app);
        $agencyModel->setAgencyId($id);
        $agencyModel->load();
        return $agencyModel->getAsArray();
    }

    public function setAgencyGroup($agencyGroup)
    {
        $this->agencyGroup = $agencyGroup;
    }

    public function getAgencyGroup()
    {
        return $this->agencyGroup = array('id' => $this->getAgencyGroupId(),'name' => $this->getAgencyGroupName());
    }

    public function setStopCredit($stopCredit)
    {
        $this->stopCredit = \App\Utility\Helpers::convertToNull($stopCredit);
    }

    public function getStopCredit()
    {
        return $this->stopCredit = array(
            'id' => $this->getStopCreditId(),
            'number' => $this->getStopCreditNumber(),
            'reason' => $this->getStopCreditReason()
        );
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
    public function getAgencyCode()
    {
        return $this->agencyCode;
    }

    /**
     * @param mixed $agencyCode
     */
    public function setAgencyCode($agencyCode)
    {
        $this->agencyCode = $agencyCode;
    }

    /**
     * @return mixed
     */
    public function getAgencyName()
    {
        return $this->agencyName;
    }

    /**
     * @param mixed $agencyName
     */
    public function setAgencyName($agencyName)
    {
        $this->agencyName = $agencyName;
    }

    /**
     * @return mixed
     */
    public function getBillingSubGroup()
    {
        return $this->billingSubGroup;
    }

    /**
     * @param mixed $billingSubGroup
     */
    public function setBillingSubGroup($billingSubGroup)
    {
        $this->billingSubGroup = $billingSubGroup;
    }

    /**
     * @return mixed
     */
    public function getAgencyApproved()
    {
        return $this->agencyApproved;
    }

    /**
     * @param mixed $agencyApproved
     */
    public function setAgencyApproved($agencyApproved)
    {
        $this->agencyApproved = Convert::toBoolean($agencyApproved);
    }

    /**
     * @return mixed
     */
    public function getPrimaryContactName()
    {
        return $this->primaryContactName;
    }

    /**
     * @param mixed $primaryContactName
     */
    public function setPrimaryContactName($primaryContactName)
    {
        $this->primaryContactName = $primaryContactName;
    }

    /**
     * @return mixed
     */
    public function getPrimaryContactEmail()
    {
        return $this->primaryContactEmail;
    }

    /**
     * @param mixed $primaryContactEmail
     */
    public function setPrimaryContactEmail($primaryContactEmail)
    {
        $this->primaryContactEmail = $primaryContactEmail;
    }

    /**
     * @return mixed
     */
    public function getPrimaryContactNotificationId()
    {
        return $this->primaryContactNotificationId;
    }

    /**
     * @param mixed $primaryContactNotificationId
     */
    public function setPrimaryContactNotificationId($primaryContactNotificationId)
    {
        $this->primaryContactNotificationId = $primaryContactNotificationId;
    }

    /**
     * @return mixed
     */
    public function getAddress1()
    {
        return $this->address1;
    }

    /**
     * @param mixed $address1
     */
    public function setAddress1($address1)
    {
        $this->address1 = $address1;
    }

    /**
     * @return mixed
     */
    public function getAddress2()
    {
        return $this->address2;
    }

    /**
     * @param mixed $address2
     */
    public function setAddress2($address2)
    {
        $this->address2 = $address2;
    }

    /**
     * @return mixed
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * @param mixed $city
     */
    public function setCity($city)
    {
        $this->city = $city;
    }

    /**
     * @return mixed
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * @param mixed $state
     */
    public function setState($state)
    {
        $this->state = $state;
    }

    /**
     * @return mixed
     */
    public function getPostCode()
    {
        return $this->postCode;
    }

    /**
     * @param mixed $postCode
     */
    public function setPostCode($postCode)
    {
        $this->postCode = $postCode;
    }

    /**
     * @return mixed
     */
    public function getPhoneNumber()
    {
        return $this->phoneNumber;
    }

    /**
     * @param mixed $phoneNumber
     */
    public function setPhoneNumber($phoneNumber)
    {
        $this->phoneNumber = $phoneNumber;
    }

    /**
     * @return mixed
     */
    public function getFaxNumber()
    {
        return $this->faxNumber;
    }

    /**
     * @param mixed $faxNumber
     */
    public function setFaxNumber($faxNumber)
    {
        $this->faxNumber = $faxNumber;
    }

    /**
     * @return mixed
     */
    public function getAgencyTableGroupId()
    {
        return $this->agencyTableGroupId;
    }

    /**
     * @param mixed $agencyTableGroupId
     */
    public function setAgencyTableGroupId($agencyTableGroupId)
    {
        $this->agencyTableGroupId = $agencyTableGroupId;
    }
    /**
     * @return mixed
     */
    public function getMobileNumber()
    {
        return $this->mobileNumber;
    }

    /**
     * @param mixed $mobileNumber
     */
    public function setMobileNumber($mobileNumber)
    {
        $this->mobileNumber = $mobileNumber;
    }

    /**
     * @return mixed
     */
    public function getAbn()
    {
        return $this->abn;
    }

    /**
     * @param mixed $abn
     */
    public function setAbn($abn)
    {
        $this->abn = $abn;
    }

    /**
     * @return mixed
     */
    public function getAccountsContact()
    {
        return $this->accountsContact;
    }

    /**
     * @param mixed $accountsContact
     */
    public function setAccountsContact($accountsContact)
    {
        $this->accountsContact = $accountsContact;
    }

    /**
     * @return mixed
     */
    public function getAccountsEmailAddress()
    {
        return $this->accountsEmailAddress;
    }

    /**
     * @param mixed $accountsEmailAddress
     */
    public function setAccountsEmailAddress($accountsEmailAddress)
    {
        $this->accountsEmailAddress = $accountsEmailAddress;
    }

    /**
     * @return mixed
     */
    public function getAccountsPhoneNumber()
    {
        return $this->accountsPhoneNumber;
    }

    /**
     * @param mixed $accountsPhoneNumber
     */
    public function setAccountsPhoneNumber($accountsPhoneNumber)
    {
        $this->accountsPhoneNumber = $accountsPhoneNumber;
    }

    /**
     * @return mixed
     */
    public function getStopCreditTableId()
    {
        return $this->stopCreditTableId;
    }

    /**
     * @param mixed $stopCreditTableId
     */
    public function setStopCreditTableId($stopCreditTableId)
    {
        $this->stopCreditTableId = $stopCreditTableId;
    }

    /**
     * @return mixed
     */
    public function getBillingCode()
    {
        return $this->billingCode;
    }

    /**
     * @param mixed $billingCode
     */
    public function setBillingCode($billingCode)
    {
        $this->billingCode = $billingCode;
    }

    /**
     * @return mixed
     */
    public function getCreditLimit()
    {
        return $this->creditLimit;
    }

    /**
     * @param mixed $creditLimit
     */
    public function setCreditLimit($creditLimit)
    {
        $this->creditLimit = $creditLimit;
    }

    /**
     * @return mixed
     */
    public function getAccountType()
    {
        return $this->accountType;
    }

    /**
     * @param mixed $accountType
     */
    public function setAccountType($accountType)
    {
        $this->accountType = $accountType;
    }

    /**
     * @return mixed
     */
    public function getIsSyncUpdate()
    {
        return $this->isSyncUpdate;
    }

    /**
     * @param $isSyncUpdate
     */
    public function setIsSyncUpdate($isSyncUpdate)
    {
        $this->isSyncUpdate = $isSyncUpdate;
    }

    /**
     * @return mixed
     */
    public function getPurchaseOrder()
    {
        return $this->purchaseOrder;
    }

    /**
     * @param mixed $purchaseOrder
     */
    public function setPurchaseOrder($purchaseOrder)
    {
        $this->purchaseOrder = $purchaseOrder;
    }

    /**
     * @return mixed
     */
    public function getAddTaxable()
    {
        return $this->addTaxable;
    }

    /**
     * @param mixed $addTaxable
     */
    public function setAddTaxable($addTaxable)
    {
        $this->addTaxable =  Convert::toBoolean($addTaxable);
    }

    /**
     * @return mixed
     */
    public function getOverseasGSTStatusApproved()
    {
        return $this->overseasGSTStatusApproved;
    }

    /**
     * @param mixed $overseasGSTStatusApproved
     */
    public function setOverseasGSTStatusApproved($overseasGSTStatusApproved)
    {
        $this->overseasGSTStatusApproved =  Convert::toBoolean($overseasGSTStatusApproved);
    }

    /**
     * @return mixed
     */
    public function getActive()
    {
        return $this->active;
    }

    /**
     * @param mixed $active
     */
    public function setActive($active)
    {
        $this->active =  Convert::toBoolean($active);
    }

    /**
     * @return mixed
     */
    public function getLastApplicationDate()
    {
        return $this->lastApplicationDate;
    }

    /**
     * @param mixed $lastApplicationDate
     */
    public function setLastApplicationDate($lastApplicationDate)
    {
        $this->lastApplicationDate = $lastApplicationDate;
    }

    /**
     * @return mixed
     */
    public function getAgencyGroupId()
    {
        return $this->agencyGroupId;
    }

    /**
     * @param mixed $agencyGroupId
     */
    public function setAgencyGroupId($agencyGroupId)
    {
        $this->agencyGroupId = $agencyGroupId;
    }

    /**
     * @return mixed
     */
    public function getAgencyGroupName()
    {
        return $this->agencyGroupName;
    }

    /**
     * @param mixed $agencyGroupName
     */
    public function setAgencyGroupName($agencyGroupName)
    {
        $this->agencyGroupName = $agencyGroupName;
    }

    /**
     * @return mixed
     */
    public function getStopCreditId()
    {
        return $this->stopCreditId;
    }

    /**
     * @param mixed $stopCreditId
     */
    public function setStopCreditId($stopCreditId)
    {
        $this->stopCreditId = \App\Utility\Helpers::convertToNull($stopCreditId);
    }

    /**
     * @return mixed
     */
    public function getStopCreditNumber()
    {
        return $this->stopCreditNumber;
    }

    /**
     * @param mixed $stopCreditNumber
     */
    public function setStopCreditNumber($stopCreditNumber)
    {
        $this->stopCreditNumber = $stopCreditNumber;
    }

    /**
     * @return mixed
     */
    public function getStopCreditReason()
    {
        return $this->stopCreditReason;
    }

    /**
     * @param mixed $stopCreditReason
     */
    public function setStopCreditReason($stopCreditReason)
    {
        $this->stopCreditReason = $stopCreditReason;
    }

    /**
     * @return mixed
     */
    public function getNetworkId()
    {
        return $this->networkId;
    }

    /**
     * @param mixed $networkId
     */
    public function setNetworkId($networkId)
    {
        $this->networkId = $networkId;
    }

    public function getCountry() {
        return $this->country;
    }

    public function setCountry($country) {
        $this->country = $country;
    }

    /**
     * @return mixed
     */
    public function getLastSubmittedJobId()
    {
        return $this->lastSubmittedJobId;
    }

    /**
     * @param mixed $lastSubmittedJobId
     */
    public function setLastSubmittedJobId($lastSubmittedJobId)
    {
        $this->lastSubmittedJobId = $lastSubmittedJobId;
    }

    /**
     * @return mixed
     */
    public function getJobCount()
    {
        return $this->jobCount;
    }

    /**
     * @param mixed $jobCount
     */
    public function setJobCount($jobCount)
    {
        $this->jobCount = $jobCount;
    }

    /**
     * @return mixed
     */
    public function getSecondaryContactName()
    {
        return $this->secondaryContactName;
    }

    /**
     * @param mixed $secondaryContactName
     */
    public function setSecondaryContactName($secondaryContactName)
    {
        $this->secondaryContactName = $secondaryContactName;
    }

    /**
     * @return mixed
     */
    public function getSecondaryContactEmail()
    {
        return $this->secondaryContactEmail;
    }

    /**
     * @param mixed $secondaryContactEmail
     */
    public function setSecondaryContactEmail($secondaryContactEmail)
    {
        $this->secondaryContactEmail = $secondaryContactEmail;
    }

    /**
     * @return mixed
     */
    public function getSecondaryContactNotificationId()
    {
        return $this->secondaryContactNotificationId;
    }

    /**
     * @param mixed $secondaryContactNotification
     */
    public function setSecondaryContactNotificationId($secondaryContactNotification)
    {
        $this->secondaryContactNotificationId = $secondaryContactNotification;
    }

    /**
     * @return mixed
     */
    public function getPrimaryWestpacToken()
    {
        return $this->primaryWestpacToken;
    }

    /**
     * @param mixed $primaryWestpacToken
     */
    public function setPrimaryWestpacToken($primaryWestpacToken)
    {
        $this->primaryWestpacToken = $primaryWestpacToken;
    }



}