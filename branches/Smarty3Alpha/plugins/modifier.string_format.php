<?php
/**
* Smarty plugin
* 
* @package Smarty
* @subpackage PluginsModifier
*/

/**
* Smarty string_format modifier plugin
* 
* Type:     modifier<br>
* Name:     string_format<br>
* Purpose:  format strings via sprintf
* 
* @link http://smarty.php.net/manual/en/language.modifier.string.format.php string_format (Smarty online manual)
* @author Monte Ohrt <monte at ohrt dot com> 
* @param string $ 
* @param string $ 
* @return string 
*/
class Smarty_Modifier_String_Format extends Smarty_Internal_PluginBase {
    static function execute($string, $format)
    {
        return sprintf($format, $string);
    } 
} 

/* vim: set expandtab: */

?>