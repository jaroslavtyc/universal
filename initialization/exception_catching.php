<?php
namespace universal;

$exceptionCatchingDir = __DIR__.'/../Debugging/';
foreach(scandir($exceptionCatchingDir) as $folder){//prohledame adresar se skripty na chytani vyjimek
	if(is_file($exceptionCatchingDir . $folder)){//every php file in that dir
		require_once($exceptionCatchingDir . $folder);//involve every script from there
	}
}