<?php
namespace universal\Check;

final class CookieCheck extends Check {

	private $isSupported;

	/**
	* Constructor initialize 'cookie' code name and avaiable relevance combination
	*
	* @return void
	*/
	public function __construct()
	{
		parent::__construct('cookie', self::RELEVANCE_RECOMMENDED_BINCODE | self::RELEVANCE_REQUIRED_BINCODE);
	}

	/**
	* Checks if Cookie technology is supported
	*
	* @param bool $forceNewCheck to check Cookie support independently of previous results
	* @return bool	about result of Cookie check
	*/
	public function isSupported($forceNewCheck = FALSE)
	{
		if (!$forceNewCheck && isset($this->isSupported))
			return $this->isSupported;
		if (isset($_COOKIE['PHPSESSID'])) {//session ID is already saved in cookie, no more tests are needed
			if (isset($_GET['cookie_check']))//redirection for reloading cookies was already performed and session ID was found in cookie, redirection again to remove cookie_check parameter
				self::redirect(FALSE);
			$this->isSupported = TRUE;

			return $this->isSupported;
		}
		try {//starting session
			Registry\Session::ensure();
		} catch (Exception $e) {
			switch($e->getCode()){
				case E_USER_WARNING:
					throw new Exception('Needs should not be determined without session and session can not be started cause of already sent headers');
				case E_ERROR:
					throw new Exception('Session can not be started by unknown reason');
				default:
					throw $e;
			}
		}
		if (isset($_GET['cookie_check'])) { //redirection for reloading cookies was already performed and session ID was not found in cookie anyway
			$this->isSupported = FALSE;

			return $this->isSupported;
		}
		self::redirect(TRUE);
	}

	/**
	* Informs about possibility of check of Cookie technology
	*
	* @return bool TRUE cause of checkable Cookie support
	*/
	public function isCheckable()
	{
		return TRUE;
	}

	private static function redirect($cookieCheckParameter)
	{
		$getParameters = '';
		if ($cookieCheckParameter)
			$getParameters .= '?' . urlencode('cookie_check') . '=' . urlencode('failed');
		elseif (isset($_GET['cookie_check']))
			unset($_GET['cookie_check']);
		foreach($_GET as $name=>$value){
			$getParameters .= '&' . urlencode($name) . '=' . urlencode($value);
		}
		if (!$cookieCheckParameter && $getParameters)
			$getParameters[0] = '?';//first character changed to question mark
		$includeProtocol = TRUE;
		$includeServer = TRUE;
		$notIncludeTrailingFiles = TRUE;
		$includeQueryComponent = FALSE;
		WebUtilities::sendRedirectionHeader(WebUtilities::getActualUrl($includeProtocol, $includeServer, $notIncludeTrailingFiles, $includeQueryComponent) . $getParameters);
		exit;
	}

}
