<?php
/**
 * User: adamS
 * Date: 18/05/2016
 * Time: 8:59 PM
 */
namespace App\Services;

use Aws\S3\S3Client;
use Elf\Core\Module;
use Elf\Exception\NotFoundException;
use \Aws\Sdk as AwsSdk;


class s3Connector extends Module
{

   /* private $config = [
        'username' => 'ftvdevelopment',
        's3' => [
            'bucket' => 'freetv-4mation',
            's3Endpoint' => 'https://s3-ap-southeast-2.amazonaws.com',
        ],
        'aws' => [
            'region'   => 'ap-southeast-2',
            'version'  => 'latest',
            'credentials' => [
                'key' => 'AKIAJCCMTCIPZEW5FQVA',
                'secret' => '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',
            ],
        ],
    ];*/

    private $client = null;

    public function init()
    {

        if(empty($this->config['s3']) || empty($this->config['iam'])) {
            throw new \Exception('No config found for the s3 Connector');
        }
        
        $aws = new AwsSdk($this->config['iam']);
        $this->client = $aws->createS3();        

    }


    /**
     * @param null $path
     * @param null $file
     * @param array $metaData
     * @return \Aws\Result
     * @throws \Exception
     */
    public function uploadFile($path = null, $file = null, $metaData = [])
    {
        if(empty($path)) {
            throw new \Exception("Invalid Path");
        }

        if(empty($file)) {
            throw new \Exception("Invalid File");
        }

        if(!is_array($metaData)) {
            throw new \Exception("Invalid Metadata");
        }

        $resourceKey = 'SourceFile';

        if(is_resource($file)) {
            $resourceKey = 'Body';
        }

        
        $config = [
            'Bucket'     => $this->config['s3']['bucket'],
            'Key'        => $path,
            $resourceKey => $file,
            'Metadata'   => $metaData,
        ];
        
        $result = $this->client->putObject($config);
        
        return [
            'url' => $result['ObjectURL'],
        ];

    }

    /**
     * @param string $filter
     * @return array
     */
    public function listfiles($filter = "")
    {

        $config = [
            'Bucket' => $this->config['s3']['bucket'],
        ];

        if(!empty($filter) && is_string($filter)) {
            $config['Prefix'] = $filter;
        }

        $iterator = $this->client->getIterator('ListObjects', $config);

        $files = [];
        foreach ($iterator as $object) {
            $files[] = $object;
        }

        return $files;
    }

    /**
     * @param string $filename
     * @return \Aws\Result
     * @throws \Exception
     */
    public function getFile($filename = "")
    {

        $config = [
            'Bucket' => $this->config['s3']['bucket'],
        ];

        if(empty($filename) || !is_string($filename)) {
            throw new \Exception("No File specified");
        }

        $config['Key'] = $filename;

        return $this->client->getObject($config);

    }
    
    /**
     * 
     * @param type $filename
     * @return type
     * @throws \Exception
     */
    public function deleteFile($filename = "")
    {
        $config = [
            'Bucket' => $this->config['s3']['bucket'],
        ];

        if(empty($filename) || !is_string($filename)) {
            throw new \Exception("No File specified");
        }

        $config['Key'] = $filename;

        return $this->client->deleteObject($config);
    }

}