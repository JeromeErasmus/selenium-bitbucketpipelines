<?php
/**
 * Created by PhpStorm.
 * User: mchan
 * Date: 31/05/16
 * Time: 8:53 AM
 */

namespace App\Services;

use Elf\Event\AbstractEvent;
use Elf\Exception\MalformedException;

class documentUpload extends AbstractEvent
{

    const TVC_UPLOAD = 1;
    const AGENCY_UPLOAD = 2;
    const INTERNAL_UPLOAD = 3;
    const SYSTEM_UPLOAD = 4;
    const SCRIPT_UPLOAD = 5;
    const INTERNAL_FILES = [
        self::INTERNAL_UPLOAD,
        self::SYSTEM_UPLOAD
    ];

    public $documentGenerationConfiguration;

    public function default_event()
    {
        throw new \Exception("No default method, please check usage");
    }

    public function __construct($app)
    {
        parent::__construct($app);
        $this->documentGenerationConfiguration = $this->app->config->get('documentGeneration');
    }

    /**
     * @param type $inputData

    Array
    (
    [jobId] => 10388781
    [userId] => 7147
    [fileContent] => 12312312312
    [fileName] => temp.txt
    [systemFilename] => 1452206206temp.txt
    )

     * @return array
     * @throws Exception
     */
    public function documentCreateFile($inputData){

        //minimum local path defined in env config
        $localPath = $this->getFolderPath();

        if($inputData['jobId']){
            //add the job id to the local path if it exists
            $localPath = $localPath . '/' . $inputData['jobId'] . '/';
        }
        if($inputData['jobId'] && $inputData['keyNumberId']){
            //add the job id to the local path if it exists
            $localPath = $localPath . '/' . $inputData['keyNumberId'] . '/';
        }
        if($inputData['jobId'] && $inputData['keyNumberId'] && $inputData['requirementId']){
            //add the job id to the local path if it exists
            $localPath = $localPath . '/' . $inputData['requirementId'] . '/';
        }
		if($inputData['jobId'] && $inputData['keyNumberId']){
            //add the job id to the local path if it exists
            $localPath = $localPath. '/' . $inputData['keyNumberId'] . '/';
        }
		if($inputData['jobId']  && $inputData['keyNumberId']  && $inputData['requirementId']){
            //add the job id to the local path if it exists
            $localPath = $localPath. '/' . $inputData['requirementId'] . '/';
        }

        if ( !is_dir( $localPath ) ) {
            //create the localpath if it doesn't exist
            mkdir( $localPath, 0777, true );
        }

        //Add the system file name to the localpath
        $localPath = $localPath . $inputData['systemFileName'];

        // In case of typos
        if(!empty($inputData['fileContent'])) {
            if( is_file( $inputData['fileContent'] ) ){
                rename($inputData['fileContent'],$localPath);
            }
            else{
                file_put_contents($localPath, base64_decode($inputData['fileContent']));
            }
            $file = array(
                'url' => $localPath,
                'local' => true,
            );
        } elseif (!empty($inputData['fileContents'])) {
            if( is_file( $inputData['fileContents'] ) ){
                rename($inputData['fileContents'],$localPath);
            }
            else{
                file_put_contents($localPath, base64_decode($inputData['fileContents']));
            }            $file = array(
                'url' => $localPath,
                'local' => true,
            );
        }
//        try { // try to use S3 and if it works, use it!
//            $s3  = $this->app->service('s3connector');
//
//            if(!is_file($localPath)) {
//                throw new Exception("Couldn't write the temp file to filesystem");
//            }
//
//            $handle = fopen($this->getFolderPath() . $inputData['fileName'], 'r+');
//
//            $file = $s3->uploadFile($inputData['systemFilename'], $handle);
//
//            fclose($handle);
//            if($this->app->config->get('documentGeneration')['uploadLocally'] == false) {
//                unlink($localPath); // clean up
//            }
//
//        } catch(\Exception $exception) { // otherwise gracefully degrade to local file storage
//
//            return [
//                'url' => $localPath,
//                'local' => true,
//            ];
//        }

        return $file;

    }

    /**
     * @param array $documentData
     * @param bool $encodeOverride param to encode and send the file
     * @return type
     */
    public function documentSendFile($documentData, $encodeOverride = false) {
        //get the file contents based on $documentData['jum_system_filename]
        // To account for large agency files breaking memory limits when being encoded, only encode system or internal files
        $documentData['fileContents'] = '';
        $encode = false;
        // If config setting is set, encode
        if ($encodeOverride == true) {
            $encode = true;
        // The below config is useful for local setups where the api and frontend are split up over different computers
        } else if (!empty($this->documentGenerationConfiguration['alwaysEncode'])) {
            $encode = true;
        // Due to the production api (as of 12th October) being on a separate server, any internally generated files need to be encoded and sent
        } else if (isset($documentData['jum_type_id']) && in_array($documentData['jum_type_id'],self::INTERNAL_FILES) ) {
            $encode = true;
        }

        if ($encode == true) {
            $documentData['fileContents'] = base64_encode(file_get_contents($documentData['jum_system_filename']));
            $documentData['fileIsEncoded'] = true;
        }
        return $documentData;
    }

    public function getFolderPath($folder = ''){

        $paths = $this->app->config->get('paths');

        if($folder === '') {
            if(empty($paths['uploadRoot'])){
                $folder = $this->app->config->defaults['paths']['documentRoot'].'\Uploads\\';
            }else{
                $folder = $paths['uploadRoot'] .'\\';
            }
        }
        return $folder;
    }

    /**
     * Constructs the input array from passed in data array
     *
     * @param array $data
     * @param string $content
     * @return array
     * @throws MalformedException
     */
    public function constructDocumentArray($data = array(), $content = ''){
        $inputData = array();

        if((!array_key_exists('jobId', $data)
                && !array_key_exists('fileName', $data)
                && !array_key_exists('systemFileName', $data))
            || (empty($content))
        ){
            throw new MalformedException("Malformed document data");
        }

        $inputData['jobId'] = $data['jobId'];
        $inputData['fileContent'] = $content;
        $inputData['fileName'] = $data['fileName'];
        $inputData['systemFileName'] = $data['systemFileName'];

        return $inputData;
    }


}