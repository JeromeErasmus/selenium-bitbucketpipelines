<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Views;
use Elf\Http\Response;


class JsonView extends \Elf\View\AbstractView {
    
    public function execute()
    {     
        if (!empty($this->viewData['status_code']) ) {
            http_response_code($this->viewData['status_code']);
        }
           
        if ( !empty($this->viewData['locationUrl']) ) {
            header('Location: '.$this->viewData['locationUrl']);
        }
        
        header('Content-Type: application/json');
        
        if (!empty($this->viewData['data'])) {
            $viewData = json_encode($this->viewData['data']);
            header('Content-Length: ' . strlen($viewData));
            echo $viewData;
        }
        exit;
    }

}
