<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Views;
use Elf\View\AbstractView;

class DefaultView extends AbstractView {
    
    public $template_name = 'home.tpl.php';

    /**
     * Executes the view, pulls template data for header, body and footer
     */
    public function execute()
    {
        $this->viewData['base_url'] = $this->app->config->WEB_PATH;
        $viewData = $this->viewData;

        if(isset($viewData['header_template'])){
            $template = $this->load_template($viewData['header_template']);
            $this->render($template);
        }

        $template = $this->load_template($viewData['template']);      

        $this->render($template);


        if(isset($viewData['footer_template'])){
            $template = $this->load_template($viewData['footer_template']);
            $this->render($template);
        }

    }
}
