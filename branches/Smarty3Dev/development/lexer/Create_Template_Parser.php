<?php
// Create Parser
passthru('C:\wamp\bin\php\php5.2.9-1\php ./ParserGenerator/cli.php smarty_internal_templateparser.y');

// Create Lexer
require_once './LexerGenerator.php';
$lex = new PHP_LexerGenerator('smarty_internal_templatelexer.plex');
$contents = file_get_contents('smarty_internal_templatelexer.php');
$contents = str_replace(array('SMARTYldel','SMARTYrdel'),array('".$this->ldel."','".$this->rdel."'),$contents);
file_put_contents('smarty_internal_templatelexer.php', $contents.'?>');
$contents = file_get_contents('smarty_internal_templateparser.php');
$contents = '<?php
/**
* Smarty Internal Plugin Templateparser
*
* This is the template parser.
* It is generated from the internal.templateparser.y file
* @package Smarty
* @subpackage Compiler
* @author Uwe Tews
*/
'.substr($contents,6);
file_put_contents('smarty_internal_templateparser.php', $contents.'?>');
copy('smarty_internal_templatelexer.php','../../distribution/libs/sysplugins/smarty_internal_templatelexer.php');
copy('smarty_internal_templateparser.php','../../distribution/libs/sysplugins/smarty_internal_templateparser.php');

?>