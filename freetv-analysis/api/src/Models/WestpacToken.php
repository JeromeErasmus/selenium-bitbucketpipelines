<?php
/**
 * Created by PhpStorm.
 * User: mchan
 * Date: 9/06/16
 * Time: 9:34 AM
 */

namespace App\Models;

use Elf\Db\AbstractAction;
use Elf\Exception\ConflictException;
use Elf\Exception\NotFoundException;
use Elf\Utility\Convert;

class westpacToken extends AbstractAction {

    private $id;
    private $list;
    private $agencyId;
    private $westpacToken;
    private $tokenExpired = 0;
    private $cardHolderName;
    private $maskedCardNumber;
    private $expiryMonth;
    private $expiryYear;
    private $creditCardSchemeId;




    protected $fieldMap = array(
        'id' => array(
            'name' => 'id',
            'type' => 'integer',
            'required' => false,
            'allowEmpty' => false,
        ),
        'westpacToken' => array(
            'name' => 'westpac_token',
            'type' => 'string',
            'required' => true,
            'allowEmpty' => false,
        ),
        'tokenExpired' => array(
            'name' => 'token_expired',
            'type' => 'integer',
            'required' => false,
            'allowEmpty' => false,
        ),
        'agencyId' => array(
            'name' => 'ag_id',
            'type' => 'integer',
            'required' => true,
            'allowEmpty' => false,
        ),
        'deletedAt' => array(
            'name' => 'deleted_at',
            'type' => 'string',
            'required' => false,
            'allowEmpty' => true,
        ),
        'cardHolderName' => array(
            'name' => 'card_holder_name',
            'type' => 'string',
            'required' => false,
            'allowEmpty' => false,
        ),
        'maskedCardNumber' => array(
            'name' => 'masked_card_number',
            'type' => 'string',
            'required' => false,
            'allowEmpty' => false,
        ),
        'expiryMonth' => array(
            'name' => 'expiry_month',
            'type' => 'numeric',
            'required' => false,
            'allowEmpty' => false,
        ),
        'expiryYear' => array(
            'name' => 'expiry_year',
            'type' => 'numeric',
            'required' => false,
            'allowEmpty' => false,
        ),
        'creditCardSchemeId' => array(
            'name' => 'credit_card_scheme_id',
            'type' => 'numeric',
            'required' => false,
            'allowEmpty' => false,
        ),
    );

    /**
     * @return mixed
     */
    public function getAgencyId()
    {
        return $this->agencyId;
    }

    public function load()
    {

        if(!$this->getAgencyId()){
            throw new \Exception("Invalid ID provided");
        } else {
            $params =   array(
                ':agencyId' => $this->getAgencyId(),
            );
        }
        $sql = "SELECT * FROM westpac_tokens WHERE ag_id = :agencyId AND deleted_at IS NULL";

        $data = $this->fetchAllAssoc($sql,$params);
        if(!empty($data)){
            foreach($data as $singleToken){
                $this->setFromArray($singleToken);
                $this->list[] = $this->getAsArray();
            }
        }
        return;
    }

    public function getAgencyPrimaryToken(){
        if(!$this->getAgencyId()){
            throw new \Exception("Invalid ID provided");
        } else {
            $params =   array(
                ':agencyId' => $this->getAgencyId(),
            );
        }
        $sql = "SELECT
                  ag.ag_name,
                  ag.ag_code,
                  wt.westpac_token,
                  wt.card_holder_name,
                  wt.masked_card_number,
                  wt.expiry_month,
                  wt.expiry_year
                FROM
                  westpac_tokens wt
                  JOIN agencies ag
                    ON ag.ag_primary_token_id = wt.id
                WHERE ag.ag_id = :agencyId
                  AND wt.token_expired <> 1
                  AND wt.deleted_at IS NULL ";

        $data = $this->fetchAllAssoc($sql,$params);
        if(!empty($data)){
            foreach($data as $singleToken){
                $this->setFromArray($singleToken);
                $this->list[] = $this->getAsArray();
            }
        }

        return;
    }

    //we are always saving new tokens with provided details, never update
    public function save()
    {
        if(!$this->getAgencyId()){
            throw new \Exception("Invalid ID provided");
        }

        if(!$this->getWestpacToken()){
            throw new \Exception("Invalid Westpac Token");
        }

        $tokenId = $this->getId();
        if(isset($tokenId)){
            return $this->update();
        }

        return $this->create();

    }

    private function create(){
        $sql = "
            INSERT INTO westpac_tokens
               ([westpac_token]
               ,[ag_id]
               ,[card_holder_name]
               ,[masked_card_number]
               ,[expiry_month]
               ,[expiry_year]
               ,[credit_card_scheme_id])
            VALUES
                  (
                      :westpacToken
                      ,:agencyId
                      ,:cardHolderName
                      ,:maskedCardNumber
                      ,:expiryMonth
                      ,:expiryYear
                      ,:creditCardSchemeId
                  )
            ";

        $params = array(
            ':westpacToken' => $this->getWestpacToken(),
            ':agencyId' => $this->getAgencyId(),
            ':cardHolderName' => $this->getCardHolderName(),
            ':maskedCardNumber' => $this->getMaskedCardNumber(),
            ':expiryMonth' => $this->getExpiryMonth(),
            ':expiryYear' => $this->getExpiryYear(),
            ':creditCardSchemeId' => $this->getCreditCardSchemeId(),
        );

        try {
            $id = $this->insert($sql,$params);
            $this->setWestpacToken($id);
            return $this->getWestpacToken();
        } catch(ConflictException $e) {
            throw new ConflictException($e->getMessage());
        }
    }

    public function deleteWestpacToken(){
        if(!$this->getWestpacToken()){
            throw new NotFoundException("Please provide a Westpac Token ID");
        }

        if(!$this->getAgencyId() || !$this->app->request->query('agencyId') || $this->getAgencyId() != $this->app->request->query('agencyId')){
            throw new Exception("Unable to continue with request, invalid Agency ID provided");
        }

        $sql = "UPDATE westpac_tokens SET deleted_at = getDate() WHERE id = :tokenId";

        $params = array(
            ':agencyId' => $this->getId(),
        );

        $rows = $this->delete($sql, $params);

        if ($rows === 0) {
            return false;
        }
        return true;

    }

    protected function update($sql = '', $params = array()){
        //early fail if this agency does not own this token
        $sql = "SELECT id FROM westpac_tokens WHERE ag_id = :ag_id";
        $tokensOwnedByAgency = $this->fetchAllAssoc($sql, array(':ag_id' => $this->agencyId));

        // because using fetchAllAssoc guarantees we receive an array of the following format
        /* array(
            '0'=> array('id'=>'0123'),
            '1'=> array('id'=>'1123'),
            '2'=> array('id'=>'2123'),
            '3'=> array('id'=>'3123')
          );
        */
        // we can use array column to search only the base array for the id matching the token agency id
        // array search returns null when no matches or invalid parameters
        if(null === array_search($this->id, array_column($tokensOwnedByAgency, 'id'))){
            throw new ConflictException("Error: Unable to access designated token");
        }

        $columns = "";
        $fieldmap = $this->fieldMap;

        foreach ($fieldmap as $property => $details) {
            if(isset($this->$property) && $details['name'] != 'id'){
                $columns .= "{$details['name']} = :$property,";
                $params[":$property"] = $this->$property;
            }
        }

        $columns = rtrim($columns,',');

        $sql = "UPDATE westpac_tokens set $columns where id = :tokenId";

        $params[':tokenId'] = $this->getId();

        try {
            parent::update($sql,$params);
            return $this->getId();
        } catch(ConflictException $e) {
            throw new ConflictException($e->getMessage());
        }
    }

    public function getExpiredToken(){
        return $this->tokenExpired;
    }

    public function setTokenExpired($tokenExpired = 0)
    {
        $this->tokenExpired = $tokenExpired;
    }

    /**
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id= $id;
    }

    /**
     * @param mixed $id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getWestpacToken()
    {
        return $this->westpacToken;
    }

    /**
     * @param mixed $agencyId
     */
    public function setAgencyId($agencyId)
    {
        $this->agencyId = $agencyId;
    }

    /**
     * @param mixed $westpacToken
     */
    public function setWestpacToken($westpacToken)
    {
        $this->westpacToken = $westpacToken;
    }

    /**
     * @return mixed
     */
    public function getCardHolderName()
    {
        return $this->cardHolderName;
    }

    /**
     * @param mixed $cardHolderName
     */
    public function setCardHolderName($cardHolderName)
    {
        $this->cardHolderName = $cardHolderName;
    }

    /**
     * @return mixed
     */
    public function getMaskedCardNumber()
    {
        return $this->maskedCardNumber;
    }

    /**
     * @param mixed $maskedCardNumber
     */
    public function setMaskedCardNumber($maskedCardNumber)
    {
        $this->maskedCardNumber = $maskedCardNumber;
    }

    /**
     * @return mixed
     */
    public function getExpiryMonth()
    {
        return $this->expiryMonth;
    }

    /**
     * @param mixed $expiryMonth
     */
    public function setExpiryMonth($expiryMonth)
    {
        $this->expiryMonth = $expiryMonth;
    }

    /**
     * @return mixed
     */
    public function getExpiryYear()
    {
        return $this->expiryYear;
    }

    /**
     * @param mixed $expiryYear
     */
    public function setExpiryYear($expiryYear)
    {
        $this->expiryYear = $expiryYear;
    }

    /**
     * @return mixed
     */
    public function getCreditCardSchemeId()
    {
        return $this->creditCardSchemeId;
    }

    /**
     * @param mixed $creditCardSchemeId
     */
    public function setCreditCardSchemeId($creditCardSchemeId)
    {
        $this->creditCardSchemeId = $creditCardSchemeId;
    }

    public function getList()
    {
        return $this->list;
    }

    public function __construct($app)
    {
        parent::__construct($app);
        $this->config = $app->config->get('security');
        return $this; // for method chaining
    }

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

    public function getAsArray()
    {
        $data = array();

        foreach($this->fieldMap as $property => $details) {
            if (property_exists($this, $property)) {
                $data[$property] = $this->$property;
            }
        }
        return $data;
    }



}