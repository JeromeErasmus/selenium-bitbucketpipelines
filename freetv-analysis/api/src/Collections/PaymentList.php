<?php
/**
 * Created by PhpStorm.
 * User: jeremy.paul
 * Date: 19/11/2015
 * Time: 1:57 PM
 */

namespace App\Collections;

use Elf\Db\AbstractCollection;

class PaymentList extends AbstractCollection {
    public function setParams($params = array())
    {

    }

    public function fetch()
    {

    }

    public function getAllPaymentMethods()
    {
        $sql = "SELECT
                pme_id as paymentMethodId,
                pme_name as paymentMethodName,
                pme_code as paymentMethodCode
                FROM dbo.payment_methods
                WHERE pme_visible <> 0";
        $data = $this->fetchAllAssoc($sql);
        return $data;
    }
}