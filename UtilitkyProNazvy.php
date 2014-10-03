<?php
namespace universal;

require_once(dirname(__FILE__).'/UtilitkyProPole.php');

class UtilitkyProNazvy{

	public static function udelejSeznamZPole($data, $oddelovac=',', $uvozovky='"'){
		$seznam = '';
		$dalsi = false;
		if(is_array($data)){
			foreach($data as $udaj){
				$seznam .= ($dalsi ? $oddelovac : '').self::udelejSeznamZPole($udaj,$oddelovac,$uvozovky);
				$dalsi = true;
			}
		}else{
			$seznam .= $uvozovky.(string)$data.$uvozovky;
		}
		return $seznam;
	}

	public static function upravFormatDat($udaj,$format = 'TEXT', $nepovolenaBilaMista = false, $pouzeKladne = false, $zaokrouhliNa = false){//podle nastaveni polozky odstrani z dat prazdna mista a pripadne preformatuje na cislo
		$vse = false;
		$cislo = 0;
		$format = strtoupper(trim($format));
		if($nepovolenaBilaMista){
			$vse = true;
		}//mohlo by tu byt i else, ale tim bychom prisli o blbuvzdornost - kdby nahodou bylo v nastaveni cislo s priznakem nepovolenaBilaMista
		if(in_array($format,array('SMALLINT','INT','TINYINT','BIGINT'))){
			$vse = true;
			$cislo = 1;
		}elseif(in_array($format,array('FLOAT','REAL','DOUBLE','DECIMAL'))){
			$vse = true;
			$cislo = 2;
		}
		$udaj = self::orezBilaMista($udaj,$vse);//smazeme bila mista v retezci, podle priznaku vse budto veskera, nebo jen na okrajich retezce
		if($cislo){//udaj ma byt ciselna hodnota, musime nyni rozhodnout, ktere znaky do tohoto cisla patri a ktere nikoli a preformatovat
			if($pouzeKladne){
				$znamenko = false;
			}else{
				$znamenko = true;
			}
			if($cislo == 1){
				$desetinne = false;
			}else{
				$desetinne = true;
			}
			$udaj = self::prevedNaCislo($udaj,$desetinne,$znamenko,false,$zaokrouhliNa);//neziska-li z udaje zadne cislice, vrati null
		}
		return $udaj;
	}

	public static function udelejCisloZeZnakovehoZnaceni($udaj){//na zaklade znakoveho oznaceni tisicu (k, M, G...) vytvori plne cislo
		$udaj = self::orezNaAlnum($udaj);//cokoli mimo cisel a zakladnich pismen je odpad
		$ciselnaCast = (int)$udaj;//cisla pred znakovym retezcem pouzijeme jako zaklad vysledku - pokud nejaka jsou
		if($ciselnaCast === 0){
			$ciselnaCast = null;
		}
		$znakoveZastoupeniTisicu = substr($udaj,strlen($ciselnaCast),1);
		switch(strtoupper($znakoveZastoupeniTisicu)){
			case 'K':
				$mocnitel = 1;
				break;
			case 'M':
				$mocnitel = 2;
				break;
			case 'G':
				$mocnitel = 3;
				break;
			case 'T':
				$mocnitel = 4;
				break;
			case 'E':
				$mocnitel = 5;
				break;
			default:
				$mocnitel = 0;
				break;
		}
		if($ciselnaCast !== null){
			$cislo = $ciselnaCast;
		}else{
			$cislo = 1;
		}
		return $cislo*pow(1000,$mocnitel);
	}

	public static function udelejNazevPromenne($text){
		return self::udelejNazevFunkce($text);
	}

	public static function udelejNazevFunkce($text){
		$text = strtolower(self::zrusDiakritiku(self::zrusSpecialniZnaky($text)));
		$nazev = '';
		$prvni = true;
		foreach(explode(' ',$text) as $slovo){
			$nazev .= ($prvni ? $slovo : ucfirst($slovo));
			$prvni = false;
		}
		return $nazev;
	}

	public static function dejZastupneZnakyBilychMist(){
		return array(
			' '		// (ASCII 32 (0x20)), bezna mezera
			,'\t'	// (ASCII 9 (0x09)), tabulator
			,'\n'	// (ASCII 10 (0x0A)), novy radek (odradkovani)
			,'\r'	// (ASCII 13 (0x0D)), navrat hlavy
			,'\0'	// (ASCII 0 (0x00)), PHP null
			,'\x0B'	// (ASCII 11 (0x0B)), svisly tabulator
		);
	}

	public static function dejKodyBilychMist(){
		return array(
			32	//bezna mezera
			,9	//tabulator
			,10	//novy radek (odradkovani)
			,13	//navrat hlavy
			,0	//PHP null
			,11	//svisly tabulator
		);
	}

	public static function srazOpakovanyZnak($text, $znak, $naPocet = 1,$presne = false){
		if(strpos($text,str_repeat($znak,($presne ? 1 : ($naPocet+1))))){//pokud v retezci vubec jsou nejaka bila mista v zajimavem poctu za sebou a ma tedy smysl je redukovat
			preg_match_all("#[^$znak]+[$znak]{0,".($presne ? 0 : $naPocet)."}#",$text,$nalezeno);//oddelime ostatni znaky od techto bilych mist
			$text = implode(($presne ? str_repeat($znak,$naPocet) : ''),$nalezeno[0]);//a opet casti spojime, tentokrat vzdy jen jednim bilym mistem na spoji
		}
		return $text;
	}

	public static function srazOpakovanaBilaMista($text,$naPocet = 1,$presne = false){//srazi bila mista na uvedeny pocet a to budto poctem presahujici, pokud neni zadano 'presne'; je-li zadano presne, pak naprosto vsechna bila mista upravi na uvedeny pocet - pro naPocet =0 a =1 nema 'presne' smysl
		foreach(self::dejBilaMista() as $bileMisto){//projdeme kazde bile misto
			$text = self::srazOpakovanyZnak($text,$bileMisto,$naPocet,$presne);
		}
		return $text;
	}

	public static function dejBilaMista(){
		$bilaMista = array();
		foreach(self::dejKodyBilychMist() as $kodBilehoMista){
			$bilaMista[] = chr($kodBilehoMista);
		}
		return $bilaMista;
	}

	public static function orezBilaMista($text,$vsude=false,$naZacatku=true,$naKonci=true){//bezne likviduje bila mista(mezery, tabulatory, odradkovani...) na zacatku a konci textu a zkracuje opakovana prazdna mista na jedno; umi tez smazat vsechna bila mista
		if($vsude){
			return str_replace(self::dejBilaMista(),'',$text);//nahradime veskera bila mista za prazdny retezec
		}elseif($naZacatku and $naKonci){
			return trim($text);//odstranime pocatecni a konecna bila mista
		}elseif($naZacatku){
			return ltrim($text);//odstranime bila mista z leva
		}else{
			return rtrim($text);//odstranime bila mista z prava
		}
	}

	public static function prevedNaCislo($text, $desetinne = false, $dbejNaZnameno = true, $prazdnoNaNulu = true, $zaokrouhliNa = false){//bezne vraci celocislenou hodnotu s ohledem na znamenko
		//pokud chceme dbat na znamenko, proverime prvni znak, zda neni znamenkem
		$text = str_replace(',','.',$text);//prevedeme pripadne carky an desetinne tecky
		preg_match_all('|[[:digit:].]|',$text,$nalezeno);
		$cislo = implode($nalezeno[0]);
		if($dbejNaZnameno and (strlen($text) > 0) and ($text[0] == '-')){
			$cislo *= (-1);
		}
		if($prazdnoNaNulu or $cislo !== ''){
			if(!$desetinne){
				return (int)$cislo;
			}else{
				$cislo = (float)$cislo;
				if($zaokrouhliNa !== false){
					$cislo = round($cislo,(int)$zaokrouhliNa);
				}
				return $cislo;
			}
		}else{
			return null;
		}
	}

	public static function udelejNazevTridy($text){
		return str_replace(' ','',(ucwords(strtolower(self::zrusDiakritiku(self::zrusSpecialniZnaky($text))))));
	}

	public static function zrusDiakritiku($text){
		$mbOriginalInternalEncoding = mb_internal_encoding();
		mb_internal_encoding('UTF-8');
		$search = 'ľĺěščřŕžýäáíéúůťóôďňĚŠČŘŽÝÁÍÉŤÚÓĎŇĽ';
		$replacement = 'llescrrzyaaieuutoodnESCRZYAIETUODNL';
		$searchLength = mb_strlen($search);
		$textLength = mb_strlen($text);
		for($i = 0; $i < $searchLength; $i++){
			$char = mb_substr($search, $i, 1);
			$replaceChar = mb_substr($replacement, $i, 1);
			while(FALSE !== ($charPosition = mb_strpos($text, $char))){
				$text = mb_substr($text,0,$charPosition) . $replaceChar . mb_substr($text,$charPosition);
				var_dump($text);
			}
		}
		mb_internal_encoding($mbOriginalInternalEncoding);
		return $text;
	}

	public static function orezNaAlnum($text,$mimoZnak = false){
		return self::zrusDiakritiku(self::zrusSpecialniZnaky($text,'',$mimoZnak));
	}

	public static function zrusSpecialniZnaky($text,$lepidlo = ' ',$mimoZnaky = false){//zlikviduje jine znaky, nez cislice a pismena, vice mezer za sebou nahradi jedinou
		$vysledek = '';
		$dalsiPovoleneZnaky = 'ľĺěščřŕžýäáíéúůťóôďňĚŠČŘŽÝÁÍÉŤÚÓĎŇĽ';
		if($mimoZnaky !== false){
			foreach((array)$mimoZnaky as $mimoZnak){
				$mimoZnak = (string)$mimoZnak;
				if(strpos($dalsiPovoleneZnaky,$mimoZnak) === false){
					$dalsiPovoleneZnaky .= $mimoZnak;
				}
			}
		}
		preg_match_all('|[[:alpha:][:digit:]'.$dalsiPovoleneZnaky.']+|', $text, $casti);//vybereme z textu povolene znaky (preg nepovazuje znaky s diakritikou za :alpha:, musime mu je vnutit sami
		$vysledek = implode((string)$lepidlo,$casti[0]);//slepime zpet kazde nalezene slovo
		return $vysledek;
	}
}