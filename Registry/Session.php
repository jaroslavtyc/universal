<?php
namespace universal\Registry;

class Session {

	public static function ensure()
	{
		if (session_id() === '') {//session does not exists yet
			if (headers_sent())//any header was already sent, session can not be started
				throw new Exception('Session can not be started cause of already sent headers', E_USER_WARNING);
			session_start();
		}
		if (session_id() === '')//session should not be started
			throw new Exception('Session can not be started by unknow cause', E_ERROR);

		return session_id();
	}
}