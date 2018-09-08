<?php

namespace App\Controllers;

use Elf\Http\Request;
use Elf\Event\RestEvent;
use Elf\Exception\MalformedException;
use Elf\Exception\NotFoundException;


class WestpacProcessing extends RestEvent
{


    //This is a test class that should be deleted

    /**
     *
     *
     Completed JSON payload
     *
        {
            "type": "capture",
            "PAN":"12341234123",
            "CVN":"123",
            "expYear":"2016",
            "expMonth":"03",
            "cardName":"123test",
            "amount":"12312312312312312321123123",
            "orderNumber":"123qweqwe",
            "currency":"AUD",
            "ECI":"IVR",
            "originalOrderNumber":"123qweqwe",
            "originalReferenceNumber":"123qweqwe",
            "authId":"qweqwe",
            "custRefNo":"123",
            "preRegCode":"321123123",
            "xid":"123",
            "cavv":"1234123412341234123412341234",
            "ipAddress":"123.123.123.123"
        }
     *
     * @param Request $request
     * @return bool
     * @throws MalformedException
     * @throws NotFoundException
     * @throws \Exception
     */
    function handlePost(Request $request)
    {

        $inputData = $request->retrieveJSONInput();
        $agencyId = $inputData['agencyId'];

        unset($inputData['agencyId']);

        $westpacProcessing = $this->app->service('westpacProcessing');

        $westpacProcessing->processTransaction($agencyId, $inputData);

    }

    function handleGet(Request $request)
    {
        throw new NotFoundException(['displayMessage' => 'This is not a valid end point']);
    }
    function handleDelete(Request $request)
    {
        throw new NotFoundException(['displayMessage' => 'This is not a valid end point']);
    }
    function handlePatch(Request $request)
    {
        throw new NotFoundException(['displayMessage' => 'This is not a valid end point']);
    }
    function handlePut(Request $request)
    {
        throw new NotFoundException(['displayMessage' => 'This is not a valid end point']);
    }
}