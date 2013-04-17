<?php
/*
* Smarty plugin
* -------------------------------------------------------------
* Type:    modifier
* Name:    fsize_format
* Version:    0.2
* Date:    2003-05-15
* Author:    Joscha Feth, joscha@feth.com
* Purpose: formats a filesize (in bytes) to human-readable format
* Usage:    In the template, use
            {$filesize|fsize_format}    =>    123.45 B|KB|MB|GB|TB
            or
            {$filesize|fsize_format:"MB"}    =>    123.45 MB
            or
            {$filesize|fsize_format:"TB":4}    =>    0.0012 TB
* Params:    
            int        size            the filesize in bytes
            string    format            the format, the output shall be: B, KB, MB, GB or TB
            int        precision        the rounding precision
            string    dec_point        the decimal separator
            string    thousands_sep    the thousands separator    
* Install: Drop into the plugin directory
* Version:
*            2007-05-24    Version 0.3    - added the peta, exa, zeta, and yeta byte magnitudes thanks to coreone (at) gmail (dot) com
*            2003-05-15    Version 0.2    - added dec_point and thousands_sep thanks to Thomas Brandl, tbrandl@barff.de
*                                    - made format always uppercase
*                                    - count sizes "on-the-fly"                                    
*            2003-02-21    Version 0.1    - initial release
* -------------------------------------------------------------
*/
function smarty_modifier_fsize_format($size,$format = '',$precision = 2, $dec_point = ".", $thousands_sep = ",")
{
    $format = strtoupper($format);

    static $sizes = array();
    
    if(!count($sizes)) {
        $b = 1024;
        $sizes["B"]        =    1;
        $sizes["KB"]    =    $sizes["B"]  * $b;        
        $sizes["MB"]    =    $sizes["KB"] * $b;
        $sizes["GB"]    =    $sizes["MB"] * $b;        
        $sizes["TB"]    =    $sizes["GB"] * $b;
        $sizes["PB"]    =    $sizes["TB"] * $b;
        $sizes["EB"]    =    $sizes["PB"] * $b;
        $sizes["ZB"]    =    $sizes["EB"] * $b;
        $sizes["YB"]    =    $sizes["ZB"] * $b;

        $sizes = array_reverse($sizes,true);
    }    
    
    //~ get "human" filesize
    foreach($sizes    AS    $unit => $bytes) {
        if($size > $bytes || $unit == $format) {
            //~ return formatted size
            return    number_format($size / $bytes,$precision,$dec_point,$thousands_sep)." ".$unit;            
        } //~ end if
    } //~ end foreach
} //~ end function
?>