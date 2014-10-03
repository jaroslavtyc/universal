<?php
if (!defined('ROOT_DIRECTORY')) {
	$cwd = getcwd();
	$cwd = str_replace('\\', '/', $cwd);
	if (strrpos($cwd, DIRECTORY_SEPARATOR) !== 0) {
		$cwd .= '/';
	}
	define('ROOT_DIRECTORY', $cwd);
}

if (!defined('DOCUMENT_ROOT')) {
	if (!empty($_SERVER['DOCUMENT_ROOT'])) {
		define('DOCUMENT_ROOT', $_SERVER['DOCUMENT_ROOT']);
	} else {
		define('DOCUMENT_ROOT', ROOT_DIRECTORY);
	}
}