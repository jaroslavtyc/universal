<?php
namespace universal;

require_once(dirname(__FILE__).'/Exception.php');

class WarningCatching extends ExceptionCatching
{

	const ERROR_LEVEL = E_WARNING;

	public function __construct($nazev = 'Varování'){//hlasi misto, kde chyba vznikla, ale nestopuje az ke korenum - vhodne pro informovani programatora
		parent::__construct($nazev,ExceptionTracking::STOPOVANI_JEN_SOUBORU_VYKAZUJICHO_CHYBU,false);
	}
}