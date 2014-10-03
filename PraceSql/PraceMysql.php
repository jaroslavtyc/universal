<?php
require_once(dirname(__FILE__).'/PraceSql.php');

abstract class PraceMysql extends PraceSql{

	const NULOVE_DATUM = '0000-00-00';
	const ZNAKOVA_SADA = 'cp1250';
	const ENGINE = 'MyISAM';
	const CHARACTER_SET = 'cp1250';

	public function __construct(){
		parent::__construct(self::MYSQL);
	}
	
	public function __destruct(){
		parent::__destruct();
	}
	
	public static function udelejEnum($data,$uvozovky = '"'){//server HBI ma nastavene magic quotes
		$enum = '';
		$carka = false;
		foreach((array)$data as $udaj){
			if(is_string($udaj) or is_int($udaj)){
				$enum .= ($carka ? ',' : '')."$uvozovky".self::predradUvozovky($udaj)."$uvozovky";
			}else{
				trigger_error("Údaj typu ".gettype($udaj)." nelze použít na ENUM",E_USER_NOTICE);
			}
			$carka = true;
		}
		return $enum;
	}	
}
