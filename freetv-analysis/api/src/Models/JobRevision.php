<?php
/**
 * Created by PhpStorm.
 * User: jeremy.paul
 * Date: 28-Apr-16
 * Time: 1:29 PM
 */

namespace App\Models;
use App\Models\KeyNumber;
use Elf\Db\AbstractAction;
use Elf\Exception\NotFoundException;

class JobRevision extends AbstractAction
{
    protected $comparisonTime;

    public function __construct ($app) {
        $currentTime = new \DateTime();
        $this->comparisonTime = $currentTime->sub(new \DateInterval('P2Y'));
        parent::__construct($app);
    }

    public function validateKeyNumberForRevisions($keyNumber)
    {
        $keyNumberModel = $this->app->Model('KeyNumber');

        $keyNumberModel->setTvcId($keyNumber['originalTvcId']);

        // Throws an exception if the key number doesn't exist so no checking needs to be done for this
        $keyNumberModel->load();

        $keyNumberData = $keyNumberModel->getAsArray();

        // Immediately fail if the tvc has been manually expired
        if(!empty($keyNumberData['tvcManuallyExpired'])) {
            throw new \Exception('The key number to be revised has expired',409);
        }

        $this->compareAssignedTime($keyNumberData['assignedDate']);

        $originalValues = array(
            'originalKeyNumber' => $keyNumberModel->getKeyNumber(),
            'originalJobId' => $keyNumberModel->getJobId(),
        );

        return $originalValues;

    }

    public function compareAssignedTime($assignedDate)
    {
        if(!empty($assignedDate)) {
            $assignedDate = new \DateTime($assignedDate);

            // If assigned date was 2 years before the current date, throw an exception
            if ($assignedDate < $this->comparisonTime) {
                throw new \Exception('One or more of the selected key numbers cannot be revised',409);
            }
        }
    }

    public function save()
    {
        // TODO: Implement save() method.
    }

    public function load()
    {
        // TODO: Implement load() method.
    }
}