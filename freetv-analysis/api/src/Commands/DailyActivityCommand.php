<?php
namespace App\Commands;


use Elf\Core\Module;
use Elf\Services\Csv;
use App\Services\Eloquent;
use Symfony\Component\Yaml\Yaml;
use App\Models\Report;


class DailyActivityCommand extends Csv{

    private $args;
    private $list;
    private $DARCSVpath;
    private $mail;
    private $today;
    private $previousWeekday;
    private $separator = ',';


    public function __construct($app)
    {
        $this->app = $app;
        $this->today = date('Y-m-d');
        $this->previousWeekday = date('Y-m-d' , strtotime('last weekday'));
        $darRoot = $this->app->config->get('paths')['DARRoot'];

        if(empty($darRoot)){
            $this->notifyError("Please set the DAR CSV path in the paths Config");
            echo "Please set the DAR CSV path in the paths Config";
            exit;
        }

        $this->DARCSVpath = $darRoot."\\DAR-".$this->today.".csv";


    }

    public function execute()
    {
        $success = $this->dailyActivity();
        echo $success;
        if(empty($success)){
            $this->notifyError();
        }
    }

    /**
     * Gets all the daily activity from previous weekday 6pm to current date 6pm
     */
    public function dailyActivity(){
        $report = new Report($this->app);
        $params['date_end'] = $this->today;
        $params['date_start'] = $this->previousWeekday;

        //get the list of people to send to
        $this->list = $report->getDailyActivityRecipientsReport();
        if(empty($this->list)){
            echo "No recipients for the DAR";
            exit;
        }

        // this gets all the current period data
        $data = $report->getDailyActivityReport($params);

        if(empty($data['data'])){
            echo 'No Data today';
            exit;
        }

        //------------------CSV------------------

        $exportSettings = array(
            'separator' => $this->separator,
            'withDoubleQuotes' => false,
            'stripHTMLTags' => true,
            'decodeHTMLEntities' => true,
            'stripHTMLEntities' => true,
        );

        $fieldsToBeRemoved = array(
            'agencyId',
            'job_submission_date',
            'job_owner',
            'job_assigned',
        );

        $data['data'] = $this->prepareCSVData($data['data'], $fieldsToBeRemoved);

        $data['headers'] = $this->prepareCSVHeader($data['headers'], $fieldsToBeRemoved);

        $data = $this->prepareCsvHeaderIntoData($data);

        $csvOutput = $this->export($data['data'], true, false, true, $exportSettings);

        file_put_contents($this->DARCSVpath, $csvOutput);

        //------------------CSV------------------

        $this->mail = $this->app->service('Mail');
        $this->mailConfig = $this->app->config->get('mail');
        $this->fromAddr = $this->mailConfig['fromAddr'];
        $this->fromName = $this->mailConfig['fromName'];
        $this->notificationConfig = $this->app->config->get('notifications');

        //$this->app->service('eloquent')->getCapsule();

        $html = "
            <p>Hi,</p>
			<p></p>
            <p>The Daily Activity Report for ". $this->today ." is attached for your reference.</p>
			<p></p>
            <p>Kind regards,</p>
            <p>Commercials Advice Team,</p>
			<p></p>
			<p>Free TV Australia</p>
            <p>44 Avenue Road</p>
            <p>Mosman NSW 2088</p>
            <p>Tel: 61 2 8968 7200 | Web: www.freetv.com.au</p>
			<p></p>
            <p>IMPORTANT NOTE</p>
            <p>Some of the information contained in the Daily Activity Report (Information) is subject to the confidentiality arrangements CAD has in place with advertisers and agencies. To ensure the confidentiality arrangements remain in place, please consider your use and disclosure of the Information carefully.</p>
			<p></p>
			<p></p>
            <p>If you have any questions on the confidentiality arrangements, please contact CAD.</p>
        ";

        foreach( $this->list as $recipient ){

            $this->mail->setAttachments(array($this->DARCSVpath));

            $this->mail->sendHtmlMessage([$recipient['email'] => ''],
                [$this->fromAddr => $this->fromName], "Daily Activity Report " . $this->today ,
                $html, array(), true);
        }
        return true;
    }
    /**
     * Mail error
     */
    private function notifyError($error = "")
    {

        if(empty($error)){
            $error = 'Daily Activity Failure';
        }
        $notifyTo = array(
            "freetv@4mation.com.au"
        );
        foreach($notifyTo as $contact){
            mail($contact, $error, $error);
        }
        exit;
    }



    /**
     * Loop through csv data and remove the elements listed in $fieldsToBeRemoved
     * @param type $csvData
     * @param type $fieldsToBeRemoved
     * @return array
     */
    private function prepareCSVData($csvData, $fieldsToBeRemoved)
    {
        foreach ($csvData as $index => $individualData) {
            foreach($fieldsToBeRemoved as $field) {
                if(isset($csvData[$index][$field])){
                    unset($csvData[$index][$field]);
                }
            }
        }
        return $csvData;
    }

    /**
     * remove the headers listed in $fieldsToBeRemoved
     * @param $csvHeader
     * @param $fieldsToBeRemoved
     * @return mixed
     */
    private function prepareCSVHeader($csvHeader, $fieldsToBeRemoved)
    {
        foreach($fieldsToBeRemoved as $field) {
            if(isset($csvHeader[$field])){
                unset($csvHeader[$field]);
            }
        }

        return $csvHeader;
    }

    /**
     * @param $data
     * @return mixed
     */
    private function prepareCsvHeaderIntoData($data) {

        // load headers into first entry as keys
        foreach ($data['headers'] as $key => $header) {

            $data['data'][0][$header] = $data['data'][0][$key];

            unset($data['data'][0][$key]);

        }

        return $data;

    }
}