<?php
/**
 * Created by PhpStorm.
 * User: jeremy.paul
 * Date: 19/11/2015
 * Time: 10:37 AM
 */

namespace App\Controllers;


use Elf\Event\RestEvent;
use Elf\Exception\NotFoundException;
use Elf\Http\Request;

class Payment extends RestEvent {

    public function handleGet(Request $request)
    {
        $id = $request->query('id');
        if (null === $id) {
            return $this->getCollection($request);
        }
        $payment = $this->app->model('Payment');
        $payment = $payment->findById($id);
        $this->set('status_code', 200);
        return $payment->getAsArray();
    }

    private function getCollection(Request $request)
    {
        $payments = $this->app->collection('Paymentlist');
        $data = $payments->getAllPaymentMethods();
        $this->set('status_code', 200);
        return $data;
    }

    public function handlePost(Request $request)
    {
        $inputData = $request->retrieveJSONInput();
        $payment = $this->app->model('Payment');

        $payment->setFromArray($inputData);

        if(!$payment->validate($payment->getAsArray())){
            $this->set('status_code', 400);
            return $payment->getErrors();
        }

        if( !($id = $payment->createRecord()) ){
            $this->set('status_code', 400);
            return array("We were unable to complete your query successfully, please try again later or contact a system administrator with code:'PA-IN-F'");
        }

        $clientId = $request->query('clientId');
        $url = "/payment/clientId/$clientId/id/$id";
        $this->set('locationUrl', $url);
        $this->set('status_code', 201);
    }

    /**
     * update a record
     * @param Request $request
     * @return type
     */
    public function handlePatch(Request $request)
    {
        if($id = $request->query('id')) {
            $inputData = $request->retrieveJSONInput();

            $paymentModel = $this->app->model('payment');
            $paymentModel->setPaymentId($id);
            $paymentModel->load();
            $paymentModel->setFromArray($inputData);

            if(!$paymentModel->validate($paymentModel->getAsArray())){
                $this->set('status_code', 400);
                return $paymentModel->getErrors();
            }

            $paymentModel->updateRecord();
            $this->set('status_code', 204);
        } else {
            throw new NotFoundException('Could Not find Payment Method');
        }
    }

    /**
     * delete it!
     * @param Request $request
     */
    public function handleDelete(Request $request)
    {
        if($id = $request->query('id')) {
            $payment = $this->app->model('payment');
            print_r($payment->deleteById($id));
            $this->set('status_code', 204);
            return;
        }
        throw new NotFoundException('Could Not find Payment Method');
    }
}