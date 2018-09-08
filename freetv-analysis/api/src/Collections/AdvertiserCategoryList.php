<?php
/**
 * Created by PhpStorm.
 * User: thimira.gunasekera
 * Date: 9/09/2015
 * Time: 4:24 PM
 */

namespace App\Collections;

class AdvertiserCategoryList extends \Elf\Db\AbstractCollection
{
    public $list;

    public function fetch()
    {
        $sql = "SELECT id, code, description, cat_group, active FROM dbo.advertiser_categories";
        $result = $this->fetchAllAssoc($sql);

        $list = array();

        foreach ($result as $key => $category) {
            $list[$key] = array(
                            'advertiserCategoryId' => $result[$key]['id'],
                            'advertiserCode' => $result[$key]['code'],
                            'description' => $result[$key]['description'],
                            'group' => $result[$key]['cat_group'],
                            'active' => $result[$key]['active'],
                            );
        }
        $this->list = $list;

        return true;
    }


    public function setParams($params=array()){}

}