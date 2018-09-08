<?php

namespace App\Services;

use Elf\Core\Module;
use Elf\Exception\ServerException;
use phpseclib\Net\SFTP;


class Ftp extends Module {
    
    /**
     * upload a csv to server using details configured in the maintenance section
     * @param string $csv
     * @param array $connectionDetails
     * @return bool
     */
    public function uploadCsv($csv,  $connectionDetails = array() ) 
    {
        $now = new \DateTime();
        $connectionDetails['fileName'] = !empty($connectionDetails['fileName']) ? $connectionDetails['fileName'] : "csv-export-" . $now->format('YmdHis') . ".csv";
        $initialPath = !empty($connectionDetails['initialPath']) ? $connectionDetails['initialPath'] : "";

        $connectionDetails['filePath'] = $this->getFilePathAndFileName($initialPath, $connectionDetails['fileName']);
        
        switch($connectionDetails['sftp']){
            case 1 :
                //SFTP
                return $this->sftpCsv($csv, $connectionDetails);
            case 0 :
                //FTP
                return $this->ftpCsv($csv, $connectionDetails);
        }
    }
    

    /**
     * Take a string and a set of paramters containing ftp server details and file name and saves file onto ftp server
     * @param string $csv string
     * @param array $connectionDetails
     * @return boolean $success true if file is uploaded correctly false for failed upload
     */
    public function ftpCsv($csv, $connectionDetails = array())
    {
        $success = false;
        $fileName = $connectionDetails['fileName'];
        $filePath = $connectionDetails['filePath'];

        // set up basic connection
        $connId = ftp_connect($connectionDetails['url']);
        if(empty($connId)){
            return false;
        }
        // login with username and password
        if(empty(ftp_login($connId, $connectionDetails['username'], $connectionDetails['password']))){
            return false;
        }

        // open file handler 
        $fileHandler = fopen($fileName,'w+');
        fwrite($fileHandler,$csv);
        rewind($fileHandler);
        
        //upload from an open file to the FTP server
        ftp_pasv($connId, true);
        if (ftp_fput($connId, $filePath, $fileHandler, FTP_ASCII)) {
            $success = true;
        } 
        
        // close the connection and the file handler
        ftp_close($connId);
        fclose ($fileHandler);
        
        //remove 
        unlink($fileName);
        return $success;
    }
    
    /**
     * Take a string and a set of paramters containing sftp server details and file name and saves file onto sftp server
     * @param type $csv
     * @param type $connectionDetails
     * @return type
     */
    public function sftpCsv($csv, $connectionDetails)
    {
        $sftp = new SFTP($connectionDetails['url']);

        // check SFTP Connection
        if ($sftp->login($connectionDetails['username'], $connectionDetails['password'])) {
            //upload file
            return $sftp->put($connectionDetails['filePath'], $csv); 
        }
        
    }

    public function getSftpFile($filename = 'data.txt', $connectionDetails)
    {
        $sftp = new SFTP($connectionDetails['url']);

        // check SFTP Connection
        if ($sftp->login($connectionDetails['username'], $connectionDetails['password'])) {
            //upload file
            $pathToFile = $this->getFilePathAndFileName($connectionDetails['initialPath'],$filename);
            return $sftp->get($pathToFile);
        }
    }
    
    /**
     * get file name with path prepended
     * @param string $initialPath
     * @param type $fileName
     * @return type
     */
    private function getFilePathAndFileName($initialPath, $fileName)
    {   
        //check filePath ends with a /, if it doesn't append it
        if(!empty($initialPath) && substr($initialPath, -1) != '/'){
            $initialPath .= '/';
        }

        // prepend the initialPath to the filename
        return  $initialPath.$fileName;
    }

}
