<?php
/**
 * Created by PhpStorm.
 * User: jeremy.paul
 * Date: 17-Jun-16
 * Time: 11:43 AM
 */

namespace App\Services;

define('_MPDF_TTFONTPATH', __DIR__. DIRECTORY_SEPARATOR . 'ttfontdata' . DIRECTORY_SEPARATOR);

class MPDFExtension extends \mPDF
{
    public function __construct($mgt = 16, $mode = '', $format = 'A4', $default_font_size = 0, $default_font = '', $mgl = 15, $mgr = 15, $mgb = 16, $mgh = 9, $mgf = 9, $orientation = 'P')
    {
        parent::__construct($mode, $format, $default_font_size, $default_font, $mgl, $mgr, $mgt, $mgb, $mgh, $mgf, $orientation);

        $this->fontdata["courier_2_electric_boogaloo"] = array(
            'R' => 'cour.ttf',            //Regular- REQUIRED
            'I' => "couri.ttf",    //Italic - OPTIONAL
            'B' => "courbd.ttf",       //Bold   - OPTIONAL
            'BI' => "courbi.ttf",       //Bold Italic - OPTIONAL
        );
        $this->defaultfooterline = 0;
        $this->available_unifonts[] = "courier_2_electric_boogaloo";
        $this->available_unifonts[] = "courier_2_electric_boogalooB";
        $this->available_unifonts[] = "courier_2_electric_boogalooI";
        $this->available_unifonts[] = "courier_2_electric_boogalooBI";

        $this->default_available_fonts = $this->available_unifonts;
//        die();
    }
}