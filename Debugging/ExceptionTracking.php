<?php
namespace universal;

class ExceptionTracking
{

	const STOPOVANI_ZADNE = 0;
	const STOPOVANI_JEN_SOUBORU_VYKAZUJICHO_CHYBU = 1;
	const TRACK_ALL_FILES_EXCEPT_FROM_UNIVERSAL = 2;
	const STOPOVANI_VSECH_SOUBORU_KROM_PREDAVANI_VYJIMEK = 3;
	const STOPOVANI_VSECH_SOUBORU_KROM_SOUBORU_STOPOVANI_VYJIMKY = 4;
	const STOPOVANI_VSECH_SOUBORU = 5;

	private static $seznamStopovani = array (
		self::STOPOVANI_ZADNE
		,self::STOPOVANI_JEN_SOUBORU_VYKAZUJICHO_CHYBU
		,self::TRACK_ALL_FILES_EXCEPT_FROM_UNIVERSAL
		,self::STOPOVANI_VSECH_SOUBORU_KROM_PREDAVANI_VYJIMEK
		,self::STOPOVANI_VSECH_SOUBORU_KROM_SOUBORU_STOPOVANI_VYJIMKY
		,self::STOPOVANI_VSECH_SOUBORU
	);

	private $stopovani;

	public function __construct($stopovani = self::STOPOVANI_JEN_SOUBORU_VYKAZUJICHO_CHYBU)
	{
		$this->stopovani = $stopovani;//stopovani ma 4 urovne; 0=zadne,1=jen file, ve kterem chyba vznikla,2=vsechny skripty mimo tech z univerzal,3=vsechny, krom tohoto,4=naprosto vsechny
	}

	public function trackout($stopovani = NULL, $zacatek = TRUE){
		$stopovani = $this->dejProvereneStopovani($stopovani);
		$description = FALSE;
		if($stopovani){//pokud mame nejake pozadavky na stopovani vyjimky
			foreach(debug_backtrace() as $poradiSouboru=>$file){//projdeme kazdy file, ktereho se vyjimka tyka
				if(
					($stopovani == self::STOPOVANI_VSECH_SOUBORU)//nemame zadna omezeni na vypis stopovani vyjimky
					or ($stopovani == self::STOPOVANI_VSECH_SOUBORU_KROM_SOUBORU_STOPOVANI_VYJIMKY and (!isset($file['file']) or ($file['file'] !== __FILE__)))//nechceme jen skript z tohoto souboru
					or ($stopovani == self::TRACK_ALL_FILES_EXCEPT_FROM_UNIVERSAL and (!isset($file['file']) or (strpos($file['file'],'univerzal') === FALSE)))//nechceme skripty z univerzalu
					or ($stopovani == self::STOPOVANI_VSECH_SOUBORU_KROM_PREDAVANI_VYJIMEK and (!isset($file['class']) or ((strpos($file['class'],'ChytaniVyjimek') === FALSE) and (strpos($file['class'],'AutoLoad') === FALSE))) and (!isset($file['file']) or ((strpos($file['file'],'ChytaniVyjimek') === FALSE) and ($file['file'] !== __FILE__))))//nechceme skripty z univerzalu
					or ($stopovani == self::STOPOVANI_JEN_SOUBORU_VYKAZUJICHO_CHYBU and $poradiSouboru === (sizeof(debug_backtrace())-1))
				){
					$description .= self::stopa($file,$zacatek);
					$zacatek = FALSE;
				}
			}
		}
		return $description;
	}

	private static function dejProvereneDetailyMistaChyby($file){
		if(!is_array($file)){
			$file = array();
		}
		$vyzadovaneDetaily = array(
			'file'
			,'line'
			,'class'
			,'function'
		);
		foreach($vyzadovaneDetaily as $detail){
			if(!isset($file[$detail])){
				$file[$detail] = FALSE;
			}
		}
		return $file;
	}

	private static function stopa($file, $zacatek){
		$file = self::dejProvereneDetailyMistaChyby($file);
		return self::dejStopu($file['file'],$file['line'],$file['class'],$file['function'],$zacatek);
	}

	public static function dejStopu($file = FALSE, $file = FALSE, $class = FALSE, $function = FALSE, $zacatek = TRUE){
		if(is_string($file) or is_string($file) or is_string($class) or is_string($function)){//pokud mame informaci alespon o jednom detailu mista chyby
			$description = "<br>\n";
			if($zacatek){
				$description .= 'Exception happened';
			}else{
				$description .= 'called';
			}
			if(is_string($file)){
				$description .= " in script <b>$file</b>";
			}
			if(is_string($file) or is_int($file)){
				$description .= " in row <b>$file</b>";
			}
			if(is_string($class)){
				if($zacatek){
					$description .= ' in class';
				}else{
					$description .= ' by class';
				}
				$description .= " <b>$class</b>";
			}
			if(is_string($function)){
				if($zacatek){
					$description .= ' in method';
				}else{
					$description .= ' by method';
				}
				$description .= " <b>$function()</b>";
			}
			return "$description";
		}else{
			return FALSE;
		}
	}

	private function dejProvereneStopovani($stopovani){
		if($stopovani !== NULL){//pokud uzivatel zadal jinou podminku
			$stopovani = (int)$stopovani;
			if(in_array($stopovani,self::$seznamStopovani)){
				return $stopovani;//vratime ji
			}else{
				throw new \RuntimeException("Neznámý požadavek na úroveň stopování ($stopovani), bude použito stopování pouze do úrovně skriptu s chybou.",E_USER_WARNING);
			}
		}
		return $this->stopovani;//jinak vratime zakladni hodnotu
	}
}