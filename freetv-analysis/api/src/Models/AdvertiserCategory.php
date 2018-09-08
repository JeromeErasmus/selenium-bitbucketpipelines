<?php
/**
 * Created by PhpStorm.
 * User: thimira.gunasekera
 * Date: 14/09/2015
 * Time: 10:40 AM
 */

namespace App\Models;


use Elf\Db\AbstractAction;
use Elf\Exception\NotFoundException;
use Elf\Exception\ConflictException;
use App\Utility\Helpers;

class AdvertiserCategory extends AbstractAction
{

    private $categoryId;
    private $advertiserCode;
    private $description;
    private $group;
    private $active;

    protected $fieldMap = array(
        'categoryId' => array('name' => 'id'),
        'advertiserCode' => array('name' => 'code'),
        'description' => array('name' => 'description'),
        'group' => array('name' => 'cat_group'),
        'active' => array('name' => 'active'),
    );

    public function findCategoryById($id)
    {
        $sql = "SELECT id FROM dbo.advertiser_categories WHERE id = :id";
        $result = $this->fetchOneAssoc($sql, array(':id' => $id));

        if (!$result) {
            throw new NotFoundException("Cannot find user with id $id");
        }

        $advertiserCategory = new AdvertiserCategory($this->app);
        $advertiserCategory->categoryId = $result['id'];
        $advertiserCategory->load();

        return $advertiserCategory;
    }

    public function findGovernmentCategoryId()
    {
        $sql = "SELECT id FROM dbo.advertiser_categories WHERE code = 'GO'";
        $result = $this->fetchOneAssoc($sql);
        return $result['id'];

    }

    public function getCategoryId()
    {
        if (empty($this->categoryId)) {
            throw new \Exception("No ID loaded");
        }
        return $this->categoryId;
    }

    public function save()
    {
        if (!empty($this->categoryId)) {
            return $this->updateRecord();
        } else {
            return $this->createRecord();
        }
    }

    public function load()
    {
        if (empty($this->categoryId)) {
            throw new \Exception("No ID set to load.");
        }

        $sql = "SELECT code,description,cat_group,active FROM dbo.advertiser_categories WHERE id = :id";
        $category = $this->fetchOneAssoc($sql, array(':id' => $this->categoryId));

        if ($category == false) {
            throw new \Exception("Cannot find category with id {$this->categoryId}");
        }
        foreach ($this->fieldMap as $fieldName => $details) {
            if (isset($category[$details['name']])) {
                $this->$fieldName = $category[$details['name']];
            }
        }
    }

    public function createRecord()
    {

        $sql = "SELECT id FROM dbo.advertiser_categories WHERE code = :code";


        $records = $this->fetchOneAssoc($sql, array(':code' => $this->advertiserCode));

        if ( $records !== false ) {     //a key already exists, fail gracefully
            throw new ConflictException("Advertiser Category Code already exists.");
        }


        $sql = "INSERT INTO dbo.advertiser_categories(code, description, cat_group, active)
                VALUES(:code, :description, :cat_group, :active)";
        try {
            $id = $this->insert($sql, array(
                ':code' => $this->advertiserCode,
                ':description' => $this->description,
                ':cat_group' => $this->group,
                ':active' => $this->active
            ));
        } catch(\Exception $e) {
            die($e->getMessage());
        }

        $this->categoryId = $id;
        return $id;
    }

    public function updateRecord()
    {
        $sql = "SELECT id FROM dbo.advertiser_categories WHERE code = :code AND id <> :id";


        $records = $this->fetchOneAssoc($sql, array(':code' => $this->advertiserCode, ':id' => $this->categoryId));

        if ( $records !== false ) {     //a key already exists, fail gracefully
            throw new ConflictException("Advertiser Category Code already exists.");
        }


        $sql = "UPDATE dbo.advertiser_categories SET
          code = :code,
          description = :description,
          cat_group = :cat_group,
          active = :active
          WHERE
          id = :id
        ";

        return $this->update($sql, array(
            ':id' => $this->categoryId,
            ':code' => $this->advertiserCode,
            ':description' => $this->description,
            ':cat_group' => $this->group,
            ':active' => $this->active
        ));
    }


    public function getAsArray()
    {
        $ret = array();
        foreach ($this->fieldMap as $key => $val) {
            $ret[$key] = $this->$key;
        }
        return Helpers::convertToBool($ret);
    }

    public function setFromArray($input)
    {
        foreach ($input as $fieldName => $value) {
            if (property_exists($this, $fieldName)) {
                $this->$fieldName = $input[$fieldName];
            }
        }
    }

    public function deleteById($id)
    {
        $sql = "DELETE FROM dbo.advertiser_categories WHERE id = :id";
        $rows = $this->delete($sql, array(":id" => $id));
        if($rows === 0)
        {
            throw new \Exception("could not delete entity with id: " . $id);
        }
    }

}