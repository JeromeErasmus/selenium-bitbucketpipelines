<?php
namespace App\Commands;


use Elf\Core\Module;
use Elf\Services\Csv;
use App\Services\Eloquent;
use Symfony\Component\Yaml\Yaml;
use App\Models\TvcExtract;
use App\Models\FtpDetail;
use App\Services\Ftp;


class TvcExtractCommand extends Csv{

    private $args;
    private $days = 5;
    private $separator = '^';

    public function __construct($app)
    {
        $this->app = $app;
    }
    
    public function execute()
    {
        $success = $this->tvcExtract();
        echo $success;
        if(empty($success)){
            $this->notifyError();
        }
    }
    
    /**
     * Extract tvc data and upload it to a fet server
     * @return bool 
     */
    public function tvcExtract()
    {
        //get the data extract
        $tvcExtract = new TvcExtract($this->app);
        $ftpDetails = new FtpDetail();
        $ftp = new Ftp($this->app);
        //set up eloquent object to be able to use the ftp object
        $eloquentInstance = new Eloquent($this->app);
        $capsule = $eloquentInstance->getCapsule();
        
        $ftpDetails = $ftpDetails->first();

        $saveParams = array(
            'fileName' => 'data.txt',
            'url' => $ftpDetails->url,
            'username' => $ftpDetails->username,
            'password' => $ftpDetails->password,
            'initialPath' => $ftpDetails->initial_path,
            'sftp' => $ftpDetails->sftp
        );

        $existingFile = $ftp->getSftpFile('data.txt',$saveParams);

        $tvcExtract->setDays($this->days);
        $data = $tvcExtract->load();
        
        if (!empty($data)) {
            //need to go on and create the csv
            $exportSettings = array(
                'separator' => $this->separator,
                'withDoubleQuotes' => false,
                'stripHTMLTags' => false,
                'decodeHTMLEntities' => false,
                'stripHTMLEntities' => false,
            );
            $csv = $this->export($data, false, false, true, $exportSettings);
            $csv = $existingFile . PHP_EOL . $csv;
            echo $ftp->uploadCsv($csv, $saveParams);
            exit; 
        }
        echo 'empty data';
        exit;
    }
    
    /**
     * Mail error
     */
    private function notifyError()
    {           
        $error = 'TVC Extract Failure';
        $notifyTo = array(
            "freetv@4mation.com.au"
            );
        foreach($notifyTo as $contact){
            mail($contact, $error, $error);
        }
    }
    
    /**
     * set days
     * @param type $days
     */
    public function setDays($days)
    {
        $this->days = $days;
    }
    
    /**
     * set separator
     * @param type $separator
     */
    public function setSeparator($separator)
    {
        $this->separator = $separator;
    }
}