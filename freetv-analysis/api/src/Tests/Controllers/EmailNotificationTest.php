<?php
/**
 * Created by PhpStorm.
 * User: jeremy.paul
 * Date: 28/09/2015
 * Time: 11:12 AM
 */
ini_set('display_errors',1);
error_reporting(E_ALL);

require_once __DIR__.'/../../../vendor/autoload.php';

use App\Config\Config;
use Elf\Application\Application;
use Elf\Exception\UnauthorizedException;
use App\Services\Notifications;
use App\Services\CommentNotifications;
use App\Services\KeyNumberNotifications;

class EmailNotificationTest extends \PHPUnit_Framework_TestCase {

    /**
     * Sends off a series of emails so the body/subject of the email can be seen (attachments are duds)
     * Elf/Application/Application.php needs to have the authenticateUser line commented out for this to work :/
     * Required config values :
            testJobId: 1041543
            testPrecheckId: 1041530
            testAdvertiserId: 1910
            testCommentId: 421
            testStationCommentId: 426
            testRequirementCommentId: 209
            testTvcId: 38130783
            testDocument: 586400
     */
    public function testEmails()
    {

        try {

            $config = new Config();
            $config->init();
            $app = new Application($config);

            $notifications = new Notifications($app);
            $commentNotifications = new CommentNotifications($app);
            $keyNumberNotifications = new KeyNumberNotifications($app);

            $notifications->test();
            $commentNotifications->test();
            $keyNumberNotifications->test();

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
