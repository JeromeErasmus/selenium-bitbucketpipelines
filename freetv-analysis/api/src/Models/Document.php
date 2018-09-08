<?php
namespace App\Models;
use Elf\Exception\ConflictException;
use Elf\Exception\NotFoundException;
use Elf\Exception\MalformedException;
use Elf\Utility\Convert;


class Document extends \Elf\Db\AbstractAction
{

    private $documentId;
    private $fileName;
    private $systemFilename;
    private $jobId;
    private $userId;
    private $country = 1;
    private $uploadTypeId;
    private $fileIsS3Link;
    private $requirementId = NULL;
    private $keyNumberId = NULL;
    private $order = 'ASC';
    protected $retrieveDeleted = false; // A state variable used when validating multiple key numbers

    public $extensionToIconMapping = array(
        'pdf' => 'fa-file-pdf-o',
        'xls' => 'fa-file-excel-o',
        'xlsx' => 'fa-file-excel-o',
        'csv' => 'fa-file-excel-o',
        'doc' => 'fa-file-word-o',
        'docx' => 'fa-file-word-o',
        'jpg' => 'fa-file-image-o',
        'jpeg' => 'fa-file-image-o',
        'gif' => 'fa-file-image-o',
        'pnd' => 'fa-file-image-o',
        'mpg' => 'fa-file-video-o',
        'mpeg' => 'fa-file-video-o',
        'mp4' => 'fa-file-video-o',
        'wmv' => 'fa-file-video-o',
        'mov' => 'fa-file-video-o',
        'm4v' => 'fa-file-video-o',
        'txt' => 'fa-file-text-o',
        'html' => 'fa-link',
        'unknown' => 'fa-question-circle',
    );

    public function __construct($app) {
        parent::__construct($app);
        return $this; // for method chaining
    }

    protected $fieldMap = array (
        'documentId' => array(
            'name' => 'jum_id',
            'required' => false,
            'type' => 'numeric'
        ),
        'jobId' => array(
            'name' => 'jum_job_id',
            'required' => false,
            'type' => 'numeric'
        ),
        'uploadTypeId' => array(
            'name' => 'jum_type_id',
            'required' => false,
            'type' => 'numeric'
        ),
        'userId' => array(
            'name' => 'jum_user_id',
            'required' => false,
            'type' => 'numeric'
        ),
        'fileName' => array(
            'name' => 'jum_original_filename',
            'required' => true,
            'type' => 'string'
        ),
        'systemFilename' => array(
            'name' => 'jum_system_filename',
            'required' => true,
            'type' => 'string'
        ),
        'fileIsS3Link' => array(
            'name' => 'jum_is_s3_link',
            'required' => false,
            'type' => 'boolean'
        ),
        'requirementId' => array(
            'name' => 'jum_requirement_id',
            'required' => false,
            'type' => 'numeric'
        ),
        'keyNumberId' => array(
            'name' => 'jum_key_number_id',
            'required' => false,
            'type' => 'numeric'
        ),
    );

    public function setJobId($jobId){
        $this->jobId = $jobId;
    }

    public function setRequirementId($requirementId){
        $this->requirementId = $requirementId;
    }

    public function setKeyNumberId($keyNumberId){
        $this->keyNumberId = $keyNumberId;
    }

    public function setOrder($order){
        $this->order = $order;
    }

    public function setUploadType($uploadTypeId){
        $this->uploadTypeId = $uploadTypeId;
    }

    public function setDocumentId($documentId){
        $this->documentId = $documentId;
    }

    /**
     * @param $retrieveDeleted
     */
    public function setRetrieveDeleted($retrieveDeleted)
    {
        $this->retrieveDeleted = $retrieveDeleted;
    }

    /**
     * @return mixed
     */
    public function getRetrieveDeleted()
    {
        return $this->retrieveDeleted;
    }

    public function load()
    {

        $sql = "SELECT 
                jum_Id,
                jum_job_id,
                jum_original_filename,
                jum_system_filename,
                jum_create_date,
                jum_modify_date,
                jum_type_id,
                jum_is_s3_link,
                jum_requirement_id,
                jum_key_number_id,
                u.user_name,
                u.user_first_name,
                u.user_last_name,
                u.user_email,
				agu.agu_email_address as user_name,
				agu.agu_first_name as user_last_name,
				agu.agu_last_name as user_last_name,
				agu.agu_email_address as user_email
                FROM job_uploaded_materials jum
                LEFT JOIN users u
                    ON jum.jum_user_id = u.user_sysid 
				LEFT JOIN agency_users agu
					ON jum.jum_user_id = agu.agu_sysid  
               ";

        //Get all documents
        $params = array();

        if(!empty($this->jobId)) {
            $sql .= "
                WHERE jum_job_id = :job_id
            ";
            $params[':job_id'] = $this->jobId;
        }
        else if(!empty($this->documentId)) {
            $sql .= "
                WHERE jum_Id = :document_id
            ";
            $params[":document_id"] = $this->documentId;
        }
        else{
            throw new MalformedException("Malformed request body");
        }


        if(!empty($this->uploadTypeId)) {
            $sql .= " AND jum_type_id = :jum_type_id";
            $params[':jum_type_id'] = $this->uploadTypeId;
        }
        else if(empty($this->uploadTypeId) && method_exists($this->app,'query') && $this->app->request()->query('clientId') != 1 ){
            $documentTypes = $this->app->config->get('documentTypes');

            $sql .= " AND( jum_type_id != ". $documentTypes['internalDocument'];
            $sql .= " OR jum_type_id != ". $documentTypes['systemDocument'];
            $sql .= " )";
        }
        if(!empty($this->requirementId)) {
            $sql .= " AND jum_requirement_id = :jum_requirement_id";
            $params[':jum_requirement_id'] = $this->requirementId;
        }
        else if(empty($this->documentId)){
            $sql .= " AND jum_requirement_id is NULL";
        }
        if(!empty($this->keyNumberId)) {
            $sql .= " AND jum_key_number_id = :jum_key_number_id";
            $params[':jum_key_number_id'] = $this->keyNumberId;
        }
        else if(empty($this->documentId) && empty($this->jobId)){
            $sql .= " AND jum_key_number_id is NULL";
        }

        /*
         * true: returns only jobs that have been deleted
         * false: returns only jobs that have not been deleted
         * NULL: returns all jobs
         */
        if(isset($this->retrieveDeleted)){
            if($this->retrieveDeleted === true) {
                $sql .= " AND deleted_at IS NOT NULL ";
            }
            elseif($this->retrieveDeleted === false){
                $sql .= " AND deleted_at IS NULL ";
            }
        }

        $sql .= "ORDER BY jum_create_date " . $this->order;

        $documents = $this->fetchAllAssoc($sql, $params);

        if (empty($documents)) {
            throw new NotFoundException("No Documents associated the provided search criteria");
        }

        $documents = $this->processAvailableDocuments($documents);

        return $documents;

    }

    /**
     * This sets the icons on the documents, matching their file extension to a font awesome icon
     * The documents are then passed on to a function to map them to their types
     * @param $availableDocuments
     */
    private function processAvailableDocuments($availableDocuments){
        if(!empty($availableDocuments)) {
            foreach($availableDocuments as $index => $document) {
                $fileExtension = strtolower(pathinfo($document["jum_system_filename"], PATHINFO_EXTENSION));
                if(array_key_exists($fileExtension,$this->extensionToIconMapping)) {
                    $availableDocuments[$index]['jum_icon_classname'] = $this->extensionToIconMapping[$fileExtension];
                } else {
                    $availableDocuments[$index]['jum_icon_classname'] = $this->extensionToIconMapping['unknown'];
                }
            }
        }

        return $availableDocuments;

    }

    public function getAsArray()
    {
        $ret = array();
        foreach ($this->fieldMap as $key => $val) {
            $ret[$key] = $this->$key;
        }
        return $ret;
    }

    public function save()
    {
        if(null == $this->documentId) { // new record so create it
            return $this->createRecord();
        }
        throw new NotFoundException("No Documents could be created");
    }

    public function createRecord()
    {
        $sql = "INSERT INTO job_uploaded_materials
                        (jum_job_id
                        ,jum_user_id
                        ,jum_original_filename
                        ,jum_system_filename
                        ,jum_cty_id
                        ,jum_type_id
                        ,jum_is_s3_link
                        ,jum_create_date
                        ,jum_modify_date
                        ,jum_requirement_id
                        ,jum_key_number_id)
                  VALUES
                        (
                            :jum_job_id,
                            :jum_user_id,
                            :jum_original_filename,
                            :jum_system_filename,
                            :jum_cty_id,
                            :jum_type_id,
                            :jum_is_s3_link,
                            GETDATE(),
                            GETDATE(),
                            :jum_requirement_id,
                            :jum_key_number_id
                        )
            ";

        // For the case of a requirement being linked to several key numbers, glean the requirement ids and tvc ids in question
        // This is done as requirementId in this case, is not the actual requirement id, but the id of the join table entry that ties tvcs to requirements

        // First create a default array
        $singleRequirementTVC = array(
            array(
                'id' => $this->requirementId,
                'rtv_tvc_id' => $this->keyNumberId
            )
        );

        // If there is a requirement id, check for multiple tvcs sharing one requirement and get their respective ids
        if(!empty($this->requirementId)) {
            $requirement_sql = "
              SELECT id, rtv_tvc_id
              FROM requirements_tvc
              WHERE rtv_req_id = (
                SELECT rtv_req_id FROM requirements_tvc WHERE id = :id
              )
            ";
            $requirement_params = array(
                ':id' => $this->requirementId
            );

            $requirementTvcJoinIds = $this->fetchAllAssoc($requirement_sql, $requirement_params);

        }

        if ( empty ( $requirementTvcJoinIds ) ) {
            $requirementTvcJoinIds = $singleRequirementTVC;
        }

        // Traverse through the tvcs and requirement ids to insert document references as necessary
        foreach ($requirementTvcJoinIds as $idObject) {
            $params = array(
                ':jum_job_id' => $this->jobId,
                ':jum_user_id' => $this->userId,
                ':jum_original_filename' => $this->fileName,
                ':jum_system_filename' => $this->systemFilename,
                ':jum_cty_id' => '1',       //just to stop it failing.
                ':jum_type_id' => $this->uploadTypeId,
                ':jum_is_s3_link' => $this->fileIsS3Link,
                ':jum_requirement_id' => $idObject['id'],
                ':jum_key_number_id' => $idObject['rtv_tvc_id'],
            );

            $id = $this->insert($sql, $params);
        }

        if (empty($id)) {
            throw new \Exception("Couldn't create a document of filename: ".$this->fileName);
        }
        $this->documentId = $id;
        return $id;
    }

    public function setFields($params)
    {
        foreach ($params as $key => $val) {
            if (array_key_exists($key, $this->fieldMap)) {
                $this->$key = $val;
            }
        }
    }
}