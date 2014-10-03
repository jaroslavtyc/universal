<?php
namespace universal;

require_once(dirname(__FILE__).'/Exception.php');

class NoticeCatching extends ExceptionCatching
{//nehlasi misto, kde chyba vznikla - vhodne pro informovani uzivatele

	const ERROR_LEVEL = E_NOTICE;

	public function __construct($nazev = 'Notice'){
		parent::__construct($nazev,ExceptionTracking::STOPOVANI_VSECH_SOUBORU_KROM_PREDAVANI_VYJIMEK,false);
	}
}