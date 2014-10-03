<?php
namespace universal\Check;

final class CssCheck extends Check {

	public function __construct()
	{
		parent::__construct('css', self::RELEVANCE_RECOMMENDED_BINCODE | self::RELEVANCE_REQUIRED_BINCODE);
	}

	/**
	* Dummy method for css technology check - which is not possible to check out via PHP
	*
	* @deprecated
	* @return null
	*/
	public function isSupported()
	{
		trigger_error('Support of cascade style technology can not be find out via PHP', E_USER_DEPRECATED);
		return NULL;
	}

	/**
	* Informs about possibility of check of CSS technology
	*
	* @return bool FALSE cause of uncheckable CSS support
	*/
	public function isCheckable()
	{
		return FALSE;
	}

}