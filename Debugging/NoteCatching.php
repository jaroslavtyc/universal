<?php
namespace universal;

require_once(dirname(__FILE__).'/Exception.php');

class NoteCatching extends ExceptionCatching
{//nehlasi misto, kde chyba vznikla - vhodne pro informovani uzivatele

	const ERROR_LEVEL = E_STRICT;

	public function __construct($nazev = 'Zpráva')
	{
		parent::__construct($nazev,ExceptionTracking::STOPOVANI_ZADNE,false);
	}
}