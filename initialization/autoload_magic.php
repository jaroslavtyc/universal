<?php
require_once(dirname(__FILE__).'/Base/Autoload.php');

function __autoload($scriptName){
	trigger_error('Using autoloading via magic ' . __FUNCTION__ . ' is discrouraged', E_DEPRECATED);
	Autoload::get()->autoload($scriptName);
}
