<?php
/**
 * Smarty plugin
 *
 * @package Smarty
 * @subpackage PluginsFunction
 */

/**
 * Smarty {is_cookies_supported} plugin
 *
 * Type:     function<br>
 * Name:     is_cookies_supported<br>
 * Purpose:  checks session cookie, if session is empty try to start session and refresh $_COOKIE by redirection - all by class CookieCheck
 *
 * @param array $params parameters
 * @param Smarty_Internal_Template $template template object
 * @return Bool about cookie support
 */
function smarty_function_is_cookies_supported($params, $template)
{
	$isSupported = CookieCheck::isSupported();//if session id is not saved in cookie, redirection for refreshing cookies will occured
	
	return $isSupported;
}
