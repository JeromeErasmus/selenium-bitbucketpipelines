<?php
namespace App\Services;

use Elf\Core\Module;

/**
 * Description of Advertiser
 *
 * @author michael
 */
class Advertiser extends Module  {

    public function merge($sourceAdvertiser, $targetAdvertiser){
        $params = [
            ":src_adv_id" => $sourceAdvertiser,
            ":trg_adv_id" => $targetAdvertiser,
        ];
        
        $sql = "BEGIN TRANSACTION; ";
        $sql .= "UPDATE [dbo].[job] SET job_adv_id = :trg_adv_id WHERE job_adv_id = :src_adv_id;";
        
        $sth = $this->app->db->prepare($sql);
        $sth->execute($params);
        
        $sql = "UPDATE [dbo].[tvcs] SET tvc_adv_id = :trg_adv_id WHERE tvc_adv_id = :src_adv_id;";
        
        $sth = $this->app->db->prepare($sql);
        $sth->execute($params);
        
        unset($params[':trg_ag_id']);
        
        $sql  = "DELETE FROM [dbo].[advertisers] WHERE adv_id = :src_adv_id; ";
        $sql .= "COMMIT TRANSACTION; ";
        
        $error = $sth->errorInfo();
            
        if ($error[0] != '00000') {
            throw new \Exception(print_r($error,1));
        }

        return true;

    }
}