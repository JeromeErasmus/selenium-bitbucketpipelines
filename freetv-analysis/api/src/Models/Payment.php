<?php
/**
 * Created by PhpStorm.
 * User: thimira.gunasekera
 * Date: 22/09/2015
 * Time: 2:14 PM
 */

namespace App\Models;


use Elf\Db\AbstractAction;
use Elf\Exception\NotFoundException;

class Payment extends AbstractAction{

    private $paymentId;
    private $paymentName;
    private $paymentCode;

    protected $fieldMap = array(
        'paymentId' => array(
            'name' => 'pme_id',
            'type' => 'numeric',
            'required' => false,
            'allowEmpty' => true
        ),
        'paymentName' => array(
            'name' => 'pme_name',
            'type' => 'string',
            'required' => true,
            'allowEmpty' => false
        ),
        'paymentCode' => array(
            'name' => 'pme_code',
            'type' => 'string',
            'required' => true,
            'allowEmpty' => false
        )
    );

    public function save()
    {
        if (empty($this->paymentId)) {
            return $this->createRecord();
        } else{
            return $this->updateRecord();
        }
    }

    public function load()
    {
        if (empty($this->paymentId)) {
            throw new \Exception("Payment ID not set");
        }

        $sql = "SELECT pme_code, pme_name FROM dbo.payment_methods WHERE pme_id = :id AND pme_visible <> 0";

        $data = $this->fetchOneAssoc($sql, [':id' => $this->paymentId]);

        $this->setFromArray($data);

    }

    public function deleteById($id)
    {
        $sql = "DELETE FROM dbo.payment_methods WHERE pme_id = :id";
        $params = array(':id' => $id);
        return $this->delete($sql,$params);
    }

    public function findById($id)
    {
        $sql = "SELECT pme_id  FROM payment_methods WHERE pme_id = :id AND pme_visible <> 0";
        $data = $this->fetchOneAssoc($sql,  array(':id' => $id));
        if ($data === false ) {
            throw new NotFoundException("Cannot find payment with ID $id");
        }

        $payment = new Payment($this->app);
        $payment->setPaymentId($data['pme_id']);
        $payment->load();
        return $payment;
    }


    public function createRecord()
    {
        $params = array(
            ':pme_code' => $this->getPaymentCode(),
            ':pme_name' => $this->getPaymentName()
        );

        $sql = "INSERT INTO payment_methods(pme_code, pme_name) VALUES (:pme_code, :pme_name)";
        $id = $this->insert($sql, $params);

        return $id;
    }

    public function updateRecord()
    {
        $params = array(
            ':pme_code' => $this->getPaymentCode(),
            ':pme_name' => $this->getPaymentName(),
            ':pme_id' => $this->getPaymentId()
        );
        $sql = "UPDATE payment_methods SET
        pme_code = :pme_code,
        pme_name = :pme_name
        WHERE
        pme_id = :pme_id AND pme_visible <> 0";

        return $this->update($sql, $params);
    }

    public function getById($id)
    {
        $payment = new Payment($this->app);
        $payment = $payment->findById($id);
        return $payment->getAsArray();
    }

    public function setFromArray($data)
    {
        foreach($this->fieldMap as $key => $mapping) { //loop through the field map and only pick up data that we want
            $setMethod = "set".$key;
            if(method_exists($this, $setMethod) && isset($data[$mapping['name']])) { // check if we can actually update this field
                $this->$setMethod($data[$mapping['name']]);
            } else if(method_exists($this, $setMethod) && isset($data[$key])) { // check if we can actually update this field
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


    /**
     * @return mixed
     */
    public function getPaymentId()
    {
        return $this->paymentId;
    }

    /**
     * @param mixed $paymentId
     */
    public function setPaymentId($paymentId)
    {
        $this->paymentId = $paymentId;
    }

    /**
     * @return mixed
     */
    public function getPaymentCode()
    {
        return $this->paymentCode;
    }

    /**
     * @param mixed $paymentCode
     */
    public function setPaymentCode($paymentCode)
    {
        $this->paymentCode = $paymentCode;
    }

    /**
     * @return mixed
     */
    public function getPaymentName()
    {
        return $this->paymentName;
    }

    /**
     * @param mixed $paymentName
     */
    public function setPaymentName($paymentName)
    {
        $this->paymentName = $paymentName;
    }



}