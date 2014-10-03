<?php
namespace universal;

require_once(dirname(__FILE__).'/ExceptionCatching.php');

class ErrorCatching extends ExceptionCatching {

	const ERROR_LEVEL = E_ERROR;

	public function __construct($name = 'Error')
	{
		parent::__construct($name, ExceptionTracking::STOPOVANI_VSECH_SOUBORU_KROM_PREDAVANI_VYJIMEK, TRUE);
	}
}