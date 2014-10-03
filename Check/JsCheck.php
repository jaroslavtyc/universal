<?php
namespace universal\Check;

final class JsCheck extends Check {

	public function __construct()
	{
		parent::__construct('js', self::RELEVANCE_RECOMMENDED_BINCODE | self::RELEVANCE_REQUIRED_BINCODE);
	}

	/**
	* Dummy method for js technology check - which is not possible to check out via PHP
	*
	* @deprecated
	* @return null
	*/
	public function isSupported()
	{
		trigger_error('Support of javascript technology can not be find out via PHP', E_USER_DEPRECATED);
		return NULL;
	}

	/**
	* Informs about possibility of check of Javascript technology
	*
	* @return bool FALSE cause of uncheckable Javascript support
	*/
	public function isCheckable()
	{
		return FALSE;
	}
}