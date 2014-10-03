<?php
	require_once(dirname(__FILE__).'/PraceSql.php');

	abstract class PraceMssql extends PraceSql{

		public function __construct(){
			parent::__construct();
		}

		public function __destruct(){
			parent::__destruct();
		}
		
		public static function udelejPlnyNazevTabulky($databaze, $tabulka, $predNazvem = '', $poNazvu = ''){
			return parent::udelejPlnyNazevTabulky($databaze, $tabulka, self::MSSQL, $predNazvem = '', $poNazvu = '');
		}
		
		public static function ohranic($text){
			return parent::ohranic($text, self::MSSQL);
		}

		public static function udelejPlnyNazevAliasuTabulky($databaze, $tabulka, $predNazvem = '', $poNazvu = ''){//vytvori nazev `databaze-tabulka`; vhodne pro zachovani plneho jmena tabulky i v aliasu
			return parent::udelejPlnyNazevAliasuTabulky($databaze, $tabulka, self::MSSQL, $predNazvem = '', $poNazvu = '');
		}
	}
?>