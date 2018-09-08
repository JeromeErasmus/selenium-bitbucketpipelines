<?php
/**
 * Created by PhpStorm.
 * User: thimira.gunasekera
 * Date: 15/01/2016
 * Time: 4:01 PM
 */
namespace App\Commands;
use Elf\Core\Module;

class AsyncNotificationSendoutCommand extends Module
{


    /**
     * @param $args
     *
     * Needs two
     */
    public function execute($args) {

        if (!isset($args['notificationType'])) {
            echo "notification type not given";
            return;
        }

        $notifications = $this->app->service('Notifications');
        $commentNotifications = $this->app->service('CommentNotifications');
        $keyNumberNotifications = $this->app->service('KeyNumberNotifications');

        try {
            switch ($args['notificationType']) {
                case "awaitingfeedback":
                    $notifications->notifyJobStatusChange($args['jobId']);
                    return;
                case "redHotJobSubmitted":
                    $notifications->notifyRedHotSubmission($args['jobId'], $args['advertiserId']);
                    return;
                case "dynamicChargeCodeJobSubmitted":
                    $notifications->notifyDynamicChargeCodeSubmission($args['jobId'], $args['advertiserId']);
                    return;
                case "agencyComment":
                    $commentNotifications->notifyAgencyReply($args['commentId'], $args['replyType']);
                    return;
                case "stationComment":
                    $commentNotifications->notifyStationReply($args['commentId'], $args['replyType']);
                    return;
                case "requirementComment":
                    $commentNotifications->notifyRequirementComment($args['commentId'], $args['replyType']);
                    return;
                case "parentStationComment":
                    $commentNotifications->notifyStationParentComment($args['commentId']);
                    return;
                case "withdrawnCadNumber":
                    $keyNumberNotifications->withdrawnCadNumber($args['tvcId']);
                    return;
                case "finalOrderForm":
                    $commentNotifications->notifyOrderForm($args['jobId'], $args['attachmentPaths']);
                    return;
                case "CADIssued":
                    $notifications->notifyCadIssued($args['jobId'], $args['attachmentPaths']);
                    return;
                case "additionalCharge":
                    $notifications->notifyAdditionalCharge($args['jobId'], $args['attachmentPaths']);
                    return;
                case "paymentFailed":
                    $notifications->notifyPaymentFailure($args['jobId']);
                    return;
                default:
                    echo "Unknown notification type";
                    return;
            }
        }catch (\Exception $e){
            echo "Something went wrong.";
            $e->getMessage();
            echo method_exists($e,'getDebugMessage') ? $e->getDebugMessage() : '';
        }
    }

}