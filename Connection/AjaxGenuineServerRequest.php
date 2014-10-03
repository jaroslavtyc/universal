<?php
namespace universal;
class AjaxGenuineServerRequest extends GenuineServerRequest {
	
	/**
	* Checks headers and determine if request is originated from this server
	*
	* @param $referer String url of qenuine referer, without protocol and server
	* @return Bool token about genuine origin
	*/
	public static function isRequestGenuie($checkSessionCookie = TRUE, $referer = NULL)
	{
		if (
			!parent::isGenuine($checkSessionCookie)//checks equality of remote address and server address
			|| empty($_SERVER['HTTP_USER_AGENT'])//ajax is expected to be called from browser
			|| empty($_SERVER['HTTP_CONNECTION'])//browser will send a http connection value
			|| empty($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest' //ajax should send XMLHttpRequest
			|| empty($_SERVER['HTTP_REFERER']) || (!empty($referer) && $_SERVER['HTTP_REFERER'] != $this->standarizeReferer($referer))//comapration of given referer and system-determined referer, if comparation required
		) {
			return FALSE;
		}
		
		return TRUE;
	}
	
	/**
	* Modify referer to local full-form if needed
	*
	* @param $referer String url of qenuine referer, should be both with or without server and protocol
	* @return String standarized referer
	*/
	private function standarizeReferer($referer)
	{
		$standarizedReferer = 'http://' . $_SERVER['HTTP_HOST'] . '/' . trim(ltrim(ltrim($referer, 'http://'), $_SERVER['HTTP_HOST']),'/') . '/';
		
		return $standarizedReferer;
	}

}