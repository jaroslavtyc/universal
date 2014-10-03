<?php
namespace universal;
class GenuineServerRequest {
	
	/**
	* Checks session id in cookie and determine if request is originated from this server
	*
	* @return Bool token about genuine origin
	*/
	public static function isGenuine($useCookies = TRUE)
	{
		if (empty($_SERVER['REMOTE_ADDR']) || empty($_SERVER['SERVER_ADDR']) || ($useCookies && !self::isCookieSessionIdCorrect())) {
			return FALSE;
		}
		
		return TRUE;
	}
	
	private static function isCookieSessionIdCorrect()
	{
		if (session_id() === '') {//session does not exists yet
			if (headers_sent())//any header was already sent, session can not be started
				throw new Exception('Authorization can not be verified without session and session can not be started cause of already sent headers');
			session_start();
		}
		if (empty($_COOKIE['PHPSESSID']) || $_COOKIE['PHPSESSID'] != session_id()) {
			return FALSE;
		}
		
		return TRUE;
	}
}