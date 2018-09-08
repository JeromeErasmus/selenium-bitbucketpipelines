<?php
/**
 * Created by PhpStorm.
 * User: jeremy.paul
 * Date: 19/11/2015
 * Time: 2:06 PM
 */

namespace Controllers;
ini_set('display_errors',1);
error_reporting(E_ALL);

require_once __DIR__.'/../../../vendor/autoload.php';

use App\Config\Config;
use App\Controllers\Payment;
use Elf\Application\Application;
use Elf\Exception\UnauthorizedException;

class PaymentTest extends \PHPUnit_Framework_TestCase {

    public function testGetByID()
    {
        $paymentComparison = json_decode(file_get_contents(__DIR__.'\data\Payment\samplePayment.json'), true);

        try {
            $config = new Config();
            $config->test['request']['server'] = array(
                'REQUEST_URI' => '/Payment/id/1',
                'ENVIRONMENT' => 'test',
            );

            $config->test['request']['headers'] = array(
                'User-Agent' => 'Chrome/43.0.2357.124',
            );

            $config->init(); // init the config again with new configs
            $app = new Application($config);
            $paymentController = new Payment($app);
            $paymentData = $paymentController->handleGet($app->request());

            $data = $paymentController->getAll();
            $this->assertTrue($data['status_code'] === 200);

            foreach(array_keys($paymentComparison) as $key) {
                if (array_key_exists($key, $paymentData)) {
                    $this->assertTrue(true);
                } else {
                    $this->fail('The keys do not match : ' . $key . ' - ' . $paymentData);
                }
            }

        }
        catch(UnauthorizedException $exception)
        {
            $this->fail($exception->getMessage());
        }
        catch(\Exception $exception)
        {
            $this->fail($exception->getMessage());
        }
    }

    public function testPost()
    {
        $paymentComparison = json_decode(file_get_contents(__DIR__.'/data/Payment/samplePostData.json'), true);

        try {
            $config = new Config();
            $config->test['request']['server'] = array(
                'REQUEST_URI' => '/Payment/clientId/1',
                'REQUEST_METHOD' => 'POST',
                'rawPostInput' => file_get_contents(__DIR__. DIRECTORY_SEPARATOR .  'data' . DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR .  'Payment' . DIRECTORY_SEPARATOR .  'samplePostData.json'),
                'ENVIRONMENT' => 'test',
            );

            $config->test['request']['headers'] = array(
                'User-Agent' => 'Chrome/43.0.2357.124',
                'Content-Type' => 'application/json',
            );

            $config->init(); // init the config again with new configs
            $app = new Application($config);

            $paymentController = new Payment($app);
            $paymentController->default_event($app->request());

            $data = $paymentController->getAll();

            $this->assertTrue($data['status_code'] === 201);
            $location = explode('/', $data['locationUrl']);
            $createdId = end($location);

            $paymentModel = new \App\Models\Payment($app);
            $paymentModel->setPaymentId($createdId);
            $paymentModel->load();
            $paymentData = $paymentModel->getAsArray();
            foreach(array_keys($paymentComparison) as $key) {

                if (array_key_exists($key, $paymentData) && ($paymentComparison[$key] == $paymentData[$key])) {
                    $this->assertTrue(true);
                } else {
                    $this->fail('Keys and/or data does not match \nKeys : ' . $key . ' - ' . $paymentData . '\nValues: ' . $paymentComparison[$key] . ' - ' . $paymentData[$key]);
                }
            }
            $paymentModel->deleteById($createdId);
        }
        catch(UnauthorizedException $exception)
        {
            $this->fail($exception->getMessage());
        }
        catch(\Exception $exception)
        {
            $this->fail($exception->getMessage());
        }
    }

    public function testPatch()
    {
        $id = 12;
        $paymentPatchData = json_decode(file_get_contents(__DIR__.'/data/Payment/samplePatchData.json'), true);

        try {
            $config = new Config();
            $config->test['request']['server'] = array(
                'REQUEST_URI' => '/payment/clientId/1/id/'.$id,
                'REQUEST_METHOD' => 'PATCH',
                'rawPostInput' => json_encode($paymentPatchData),
                'ENVIRONMENT' => 'test',
            );

            $config->test['request']['headers'] = array(
                'User-Agent' => 'Chrome/43.0.2357.124',
                'Content-Type' => 'application/json',
            );

            $config->init(); // init the config again with new configs
            $app = new Application($config);

            $paymentController = new Payment($app);
            $paymentController->handlePatch($app->request());

            $data = $paymentController->getAll();

            $this->assertTrue($data['status_code'] === 204);

            $paymentModel = new \App\Models\Payment($app);
            $paymentModel->getById($id);
            $paymentData = $paymentModel->getAsArray(true);

            foreach($paymentData as $key => $value)
            {
                $this->assertTrue($paymentData[$key] == $value);
            }
        }
        catch(UnauthorizedException $exception)
        {
            $this->fail($exception->getMessage());
        }
        catch(\Exception $exception)
        {
            $this->fail($exception->getMessage());
        }
    }
}
