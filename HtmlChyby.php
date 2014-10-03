<?php
namespace universal;

abstract class HtmlChyby extends BaseClass {

	const VSECHNY_METODY_NAVRATU = 111;//delka se musi rovnat celkovemu poctu konkretnich metod navratu
	const METODA_NAVRATU = self::METODA_NAVRATU_SESSION;
	const METODA_NAVRATU_GET = 1;//'GET';
	const METODA_NAVRATU_SESSION = 10;//'SESSION';
	const METODA_NAVRATU_SQL = 100;//'SQL';
	const PREDPONA_GET_CHYB = 'HtmlChyby_';
	const ODDELOVAC_GET_CHYB = '#';
	const NANEJVYS_POCET_CHYB = -1;

	private $noveChyby = array();
	private $stareChyby = array();
	private $metodaNavratu;
	private $nazevProjektu;

	public function __construct($metodaNavratu = self::METODA_NAVRATU){
		$this->nazevProjektu = get_class($this);
		$this->metodaNavratu = $metodaNavratu;//prednastavenou metodu navratu nastavime pri inicializaci tridy
		$this->nastavStareChyby();
	}

	protected function dejMetoduNavratu($kodMetody, $posledniPokus = false){
		$metodaNavratu = false;
		$kodMetody = $this->dejProverenyKodMetodyNavratu($kodMetody);
		if($kodMetody !== false){
			switch((int)$kodMetody){
				case self::METODA_NAVRATU_GET:
					$metodaNavratu = 'GET';
					break;
				case self::METODA_NAVRATU_SESSION:
					$metodaNavratu = 'SESSION';
					break;
				case self::METODA_NAVRATU_SQL:
					$metodaNavratu = 'SQL';
					break;
				case self::VSECHNY_METODY_NAVRATU:
					trigger_error("Z požadavku na všechny metody návratu nelze určit jen jednu metodu návratu.",E_USER_WARNING);
					break;
				default:
					trigger_error("Neznámý kód metody návratu (".(string)$kodMetody.") a nezdařený pokus o použití přednastavené metody.",E_USER_WARNING);
			}
		}
		return $metodaNavratu;
	}

	protected function dejProverenyKodMetodyNavratu($kodMetody, $posledniPokus = false){
		$kodMetody = (int)$kodMetody;
		switch($kodMetody){
			case self::METODA_NAVRATU_GET:
				break;
			case self::METODA_NAVRATU_SESSION:
				break;
			case self::METODA_NAVRATU_SQL:
				break;
			case self::VSECHNY_METODY_NAVRATU:
				break;
			default:
				if(!$posledniPokus){
					$kodMetody = $this->dejProverenyKodMetodyNavratu($this->metodaNavratu, true);
					trigger_error("Neznámý kód metody návratu (".(string)$kodMetody."). Bude použit kód $kodMetody",E_USER_WARNING);
				}else{
					trigger_error("Neznámý kód metody návratu (".(string)$kodMetody.") a nezdařený pokus o použití přednastaveného kódu.",E_USER_WARNING);
				}
				$kodMetody = false;
		}
		return $kodMetody;
	}

	public function zapamatujChybu($obsahChyby, $druhChyby = false, $kodMetodyNavratu = false){
		if($kodMetodyNavratu === false){
			$kodMetodyNavratu = $this->metodaNavratu;
		}
		if(($metodaNavratu = $this->dejMetoduNavratu($kodMetodyNavratu))){
			if($druhChyby !== false){
				$druhChyby = (string)$druhChyby;
				/*if($kodMetodyNavratu == self::METODA_NAVRATU_GET){
					$druhChyby = self::PREDPONA_GET_CHYB.$druhChyby;//abychom odlisili noveChyby od ostatnich informaci v GET, pridame jim predponu
				}*/
				if(!isset($this->noveChyby[$metodaNavratu][$druhChyby])){
					$this->noveChyby[$metodaNavratu][$druhChyby] = array();
				}
				$this->noveChyby[$metodaNavratu][$druhChyby][] = $obsahChyby;
			}else{
				$this->noveChyby[$metodaNavratu][] = $obsahChyby;
			}
		}else{
			trigger_error("Neznámá metoda návratu, chybu '$obsahChyby'".(($druhChyby !== false) ? " druhu ".(string)$druhChyby : '')." nebude možné předat.",E_USER_WARNING);
		}
	}

	public function navratSChybami($cilNavratu = 'index.php', $obsahChyby = false, $druhChyby = false, $metodaNavratu = false){
		if($metodaNavratu === false){
			$metodaNavratu = $this->metodaNavratu;
		}
		$WebUtilities = new WebUtilities();
		if($obsahChyby !== false){
			$this->zapamatujChybu($obsahChyby, $druhChyby, $metodaNavratu);
		}
		$header = 'Location: ' . $WebUtilities::getActualUrl(TRUE, TRUE, TRUE).$cilNavratu;
		if(isset($this->noveChyby[$this->dejMetoduNavratu(self::METODA_NAVRATU_GET)])){
			$poradi = 1;
			$ciselneIndexovaneChyby = array();
			foreach($this->noveChyby[$this->dejMetoduNavratu(self::METODA_NAVRATU_GET)] as $index=>$chybaCiSkupina){
				if(is_string($index) and is_array($chybaCiSkupina)){
					$slouceneChyby = $index;//prvni udaj bude nazev skupiny chyb - kvuli nahrade mezer v nazvu promennych musime tento udaj prenaset jako hodnotu promenne
					$oddeleni = true;
					foreach($chybaCiSkupina as $chyba){
						$slouceneChyby .= ($oddeleni ? '%'.dechex(ord(self::ODDELOVAC_GET_CHYB)) : '').(string)$chyba;//vice chyb jednoho druhu pro usporu slucujeme do retezce, ve kterem jsou odlisene mrizkou
						//$oddeleni = true;
					}
					$ciselneIndexovaneChyby[self::PREDPONA_GET_CHYB.$poradi] = $slouceneChyby;
				}
				$poradi++;
			}
			$this->noveChyby[$this->dejMetoduNavratu(self::METODA_NAVRATU_GET)] = $ciselneIndexovaneChyby;
			$get = WebUtilities::prevedPoleNaGet($this->noveChyby[$this->dejMetoduNavratu(self::METODA_NAVRATU_GET)]);
			$header .= "?$get";
			unset($this->noveChyby[$this->dejMetoduNavratu(self::METODA_NAVRATU_GET)]);
		}
		if(isset($this->noveChyby[$this->dejMetoduNavratu(self::METODA_NAVRATU_SESSION)])){
			Registry\Session::ensure();
			$_SESSION[$this->nazevProjektu] = $this->noveChyby[$this->dejMetoduNavratu(self::METODA_NAVRATU_SESSION)];
		}
		if(isset($this->noveChyby[$this->dejMetoduNavratu(self::METODA_NAVRATU_SQL)])){
			trigger_error("Návrat chyb pomocí SQL není zatím podporován.",E_USER_NOTICE);
		}
		$Chyby = new $this->nazevProjektu;
		header($header);
		exit;//exit je pro jistotu, kdyby nefungovalo presmerovani, aby program nepokracoval v neplechach (pokud se vracime s chybami, tak zjevne nejake nasekal)
	}

	public function pocetChyb(){//opousteno
		return $this->dejPocetChyb();
	}

	public function existError($kodMetody = self::VSECHNY_METODY_NAVRATU, $staraChyba = true){
		return $this->dejPocetChyb($kodMetody,1,$staraChyba);
	}

	public function existOldError($kodMetody = self::VSECHNY_METODY_NAVRATU){
		return $this->existError($kodMetody, true);
	}

	public function jeNovaChyba($kodMetody = self::VSECHNY_METODY_NAVRATU){
		return $this->existError($kodMetody, false);
	}

	public function dejPocetNovychChyb($kodMetody = self::VSECHNY_METODY_NAVRATU, $nanejvys = self::NANEJVYS_POCET_CHYB){
		return $this->dejPocetChyb($kodMetody, $nanejvys, false);
	}

	public function dejPocetChyb($kodMetody = self::VSECHNY_METODY_NAVRATU, $nanejvys = self::NANEJVYS_POCET_CHYB, $stareChyby = true){//vraci pocet starych chyb
		if($stareChyby){
			$chyby = &$this->stareChyby;
		}else{
			$chyby = &$this->noveChyby;
		}
		$kodMetody = $this->dejProverenyKodMetodyNavratu($kodMetody);
		$nanejvys = (int)$nanejvys;
		if(($nanejvys < 1) and ($nanejvys !== self::NANEJVYS_POCET_CHYB)){
			trigger_error("Zadané omezení maxima pro sčítání chyb je chybné (".(string)$nanejvys."). Bude použito neomezené sčítání.",E_USER_NOTICE);
			$nanejvys = self::NANEJVYS_POCET_CHYB;
		}
		if($kodMetody !== false){
			$pocet = 0;
			if($kodMetody !== self::VSECHNY_METODY_NAVRATU){
				$chtenaMetodaNavratu = $this->dejMetoduNavratu($kodMetody);
			}
			foreach((array)$chyby as $metodaNavratu=>$chybyJedneMetody){
				if(($kodMetody === self::VSECHNY_METODY_NAVRATU) or ($chtenaMetodaNavratu == $metodaNavratu)){
					foreach($chybyJedneMetody as $index=>$chybaCiSkupina){
						if(is_string($index) and is_array($chybaCiSkupina)){
							$pocet += sizeof($chybaCiSkupina);
						}else{
							$pocet++;
						}
						if(($nanejvys > 0) and ($pocet == $nanejvys)){
							break 2;
						}
					}
				}
			}
			return $pocet;
		}else{
			return false;
		}
	}

	public function forgotErrors(){
		if(isset($_SESSION[$this->nazevProjektu])){
			unset($_SESSION[$this->nazevProjektu]);
		}
		$this->noveChyby = array();
	}

	protected function nastavStareChyby($kodMetody = self::VSECHNY_METODY_NAVRATU){
		$kodMetody = $this->dejProverenyKodMetodyNavratu($kodMetody);
		$pracovniKodMetody = (string)$kodMetody;
		for($i = 0; $i < strlen($pracovniKodMetody); $i++){
			$zpracovavanyKodMetody = $this->dejProverenyKodMetodyNavratu($pracovniKodMetody[$i].str_repeat('0',$i));
			$zpracovavanyKodMetody = $this->dejProverenyKodMetodyNavratu($zpracovavanyKodMetody);
			$zpracovavanaMetoda = $this->dejMetoduNavratu($zpracovavanyKodMetody);
			switch($zpracovavanyKodMetody){
				case self::METODA_NAVRATU_GET://chceme-li nacist noveChyby predane pres GET
					if(!isset($this->stareChyby[$zpracovavanaMetoda])){//jestlize zatim nemame uloziste pro noveChyby z GET
						$this->stareChyby[$zpracovavanaMetoda] = array();
					}
					if(isset($_GET)){
						foreach((array)$_GET as $skupina=>$mozneChyby){
							if(strpos($skupina,self::PREDPONA_GET_CHYB) === 0){//je-li polozka GET identifikovana jako chyba puvodem z teto tridy
								/*$skupina = str_replace(self::PREDPONA_GET_CHYB,'',$skupina);//odstranime predponu
								$skupina = str_replace("\t",' ',$skupina);//kvuli chovani PHP GET jsme puvodne nahradili mezery za tabulatory a nyni je vracime zpet*/
								$mozneChyby = explode(self::ODDELOVAC_GET_CHYB,$mozneChyby);
								$skupina = $mozneChyby[0];//nazev skupiny chyb jsme kvuli zachovani vsech znaku prenaseli jako prvni polozku
								unset($mozneChyby[0]);
								$this->stareChyby[$zpracovavanaMetoda][$skupina] = $mozneChyby;
							}
						}
					}
					break;
				case self::METODA_NAVRATU_SESSION:
					Registry\Session::ensure();
					if(isset($_SESSION[$this->nazevProjektu])){
						$this->stareChyby[$zpracovavanaMetoda] = $_SESSION[$this->nazevProjektu];
					}else{
						$this->stareChyby[$zpracovavanaMetoda] = array();
					}
					break;
				case self::METODA_NAVRATU_SQL:
					$this->stareChyby[$zpracovavanaMetoda] = array();
					break;
				default:
					trigger_error("Neznámý kód metody návratu (".(string)$zpracovavanyKodMetody.")",E_USER_WARNING);
			}
		}
	}

	public function getErrors($kodMetody = self::VSECHNY_METODY_NAVRATU, $zapomenStareChyby = true){
		$kodMetody = $this->dejProverenyKodMetodyNavratu($kodMetody);
		$chyby = array();
		if($kodMetody !== self::VSECHNY_METODY_NAVRATU){
			$methodName = $this->dejMetoduNavratu($kodMetody);
			if (isset($this->stareChyby[$methodName]))
				$chyby = $this->stareChyby[$methodName];
		}else{
			$chyby = $this->stareChyby;
		}

		return $chyby;
	}

	public function dejSeznamPripadnychChyb($metodaNavratu = HtmlChyby::VSECHNY_METODY_NAVRATU, $zapomenStareChyby = true){
		$seznam = false;
		if($this->existOldError()){
			$seznam = '<div class="chyby">';
			foreach($this->getErrors($metodaNavratu,$zapomenStareChyby) as $metodaNavratu=>$chybyMetody){
				foreach($chybyMetody as $nazevSkupiny=>$chybySkupiny){
					$seznam .= "\n<h4>$nazevSkupiny:</h4><ul>";
					foreach($chybySkupiny as $chyba){
						$seznam .= "<li>$chyba</li>\n";
					}
					$seznam .= '</ul>';
				}
			}
			$seznam .= '</div>';
		}
		return $seznam;
	}
}
