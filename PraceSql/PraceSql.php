<?php
namespace universal;

require_once(dirname(__FILE__).'/../Class.php');
require_once(dirname(__FILE__).'/../UtilitkyProPole.php');

abstract class PraceSql extends BaseClass {

	const MYSQL = 'MySQL';
	const MSSQL = 'MSSQL';
	const NULOVY_CAS = '0000-00-00 00:00:00';//u nekterych verzi MSSQL nelze pouzit nizsi cas nez 1900 +-
	const MINIMALNI_CAS = '1900-01-01 00:00:00';

	protected $Pripojeni;
	protected $prostredi;

	public function __construct($prostredi){
		$this->prostredi = $prostredi;
	}

	public function __destruct(){
		$this->odpojSql($this->Pripojeni);
	}

	//vlastnosti, pres ktere uzavirame spojeni, musi byt nanejvis protected, ne-li public, jinak se k nim tato funkce nedostane, nebo dodane jako parametry
	//reaguje na podtridy PripojeniSql, nic jineho
	protected function odpojSql(){//POZOR!! pokud predas stylem Singleton promennou obsahujici odkaz na zive pripojeni, zruseni kterekoli ze dvou trid zrusi pripojeni
		$prohledejPole = false;
		if(sizeof(func_get_args()) > 0){//dostali jsme nejake parametry, budeme hledat pripojeni jen v nich, ale zato budeme prohledavat i pole a to rekurzivne
			$vlastnosti = func_get_args();
			$prohledejPole = true;
		}else{
			$vlastnosti = get_object_vars($this);
		}
		foreach($vlastnosti as &$vlastnost){//projdeme veskere vlastnosti tidy(teto a dcerinych)
			$this->odpojPripadnePripojeni($vlastnost,$prohledejPole);
		}
	}

	private function odpojPripadnePripojeni($vlastnost,$prohledejPole){
		if($prohledejPole and is_array($vlastnost)){
			foreach($vlastnost as &$podvlastnost){
				$this->odpojPripadnePripojeni($podvlastnost,$prohledejPole);
			}
		}elseif(is_object($vlastnost)){
			if(is_subclass_of($vlastnost,'PripojeniSql')){//jestlize jsou potomkem abstraktni tridy PripojeniSql
				$vlastnost->odpojSe();//zkusime odpojit pripojeni, ktere se na danou vlastnost tridy vaze
			}
		}
	}

	public static function udelejPlnyNazevTabulky($databaze, $tabulka, $predNazvem = '', $poNazvu = '', $bezKontroly = false){
		if(!$bezKontroly){
			$databaze = self::udelejNazevDatabaze((string)$databaze);
			$tabulka = self::udelejNazevTabulky((string)$predNazvem.' '.$tabulka.' '.(string)$poNazvu);
		}else{
			$tabulka = (string)$predNazvem.(string)$tabulka.(string)$poNazvu;
		}
		return self::ohranic($databaze,$this->prostredi).'.'.(($this->prostredi == self::MSSQL) ? self::ohranic('dbo',$this->prostredi).'.' : '').self::ohranic($tabulka,$this->prostredi);
	}

	public static function ohranic($text, $jenJeLiTreba = true, $nechBytNull = true, $nechBytPrazdno = true, $jinymZnakem = false){
		if(!$jenJeLiTreba or self::jeTrebaOhranicit($text)){//pokud ohraniceni vyzadujeme, nebo obsah vyhraniceni vyzaduje
			if(!$nechBytNull or (strcasecmp($text,'NULL') !== 0)){//pokud chceme ohranicit i text null nebo nejde o text null
				if(!$nechBytPrazdno or ($text !== '')){//jestlize chceme ohranicit i prazdny retezec nebo nejde o prazdny retezec
					if(!$jinymZnakem){
						switch($this->prostredi){
							case self::MYSQL :
								$text = "`$text`";
								break;
							case self::MSSQL :
								$text = "[$text]";
								break;
							default:
								trigger_error("Neznámé SQL prostředí ($this->prostredi)! Nevím, jak ohraničit SQL objekty.");
								exit;
						}
					}else{
						$text = "$jinymZnakem$text$jinymZnakem";
					}
				}
			}
		}
		return $text;
	}

	public static function jeTrebaOhranicit($text){
		preg_match_all('|[[:alpha:][:digit:]_]+|', $text, $vycisteno);
		$vycisteno = implode('',$vycisteno[0]);
		return($vycisteno !== $text);
	}

	public static function udelejNazevSloupce($text){ //vsechna prvni pismena velka, zruseni mezer - "dobry vecer" => "DobryVecer"
		$text = UtilitkyProNazvy::zrusDiakritiku(UtilitkyProNazvy::zrusSpecialniZnaky($text));
		return str_replace(' ', '', (ucwords(strtolower($text))));
	}

	public static function udelejNazevTabulky($nazev, $predNazvem = '', $poNazvu = ''){
		return self::udelejNazevDatabaze((string)$predNazvem.' '.$nazev.' '.(string)$poNazvu);
	}

	public static function udelejNazevDatabaze($nazev, $predNazvem = '', $poNazvu = ''){ //vsechna pismena mala, misto mezer podtrzitka - "Kuala Lumpur je rajem lumpu" => "kuala_lumpur_je_rajem_lumpu"
		if(strlen((string)$predNazvem) > 0){
			$nazev = (string)$predNazvem.' '.$nazev;
		}
		if(strlen((string)$poNazvu) > 0){
			$nazev = $nazev.' '.(string)$poNazvu;
		}
		$nazev = UtilitkyProNazvy::zrusDiakritiku(UtilitkyProNazvy::zrusSpecialniZnaky($nazev));
		return str_replace(' ', '_', (strtolower($nazev)));
	}

	public static function udelejPlnyNazevAliasuTabulky($databaze, $tabulka, $predNazvem = '', $poNazvu = ''){//vytvori nazev `databaze-tabulka`; vhodne pro zachovani plneho jmena tabulky i v aliasu
		return self::ohranic("$databaze-".self::udelejNazevTabulky((string)$predNazvem.' '.$tabulka.' '.(string)$poNazvu), $this->prostredi);
	}

	public static function zjednodusUvozovky($text){
		return str_replace('"', "'", $text);
	}

	public static function zdvojUvozovky($text){
		return str_replace("'", '"', $text);
	}

	/*public static function predrad($texty, $znaky){
		if(is_array($texty)){
			foreach($texty as &$text){
				$text = self::predrad($text, $znaky);
			}
		}else{
			foreach($znaky as $znak){
				$texty = str_replace($znak,"\\$znak",$texty);
			}
		}
		return $texty;
	}*/

	public static function predradUvozovky($text){
		return str_replace('"', '\\"', str_replace("'", "\\'", $text));
	}
}