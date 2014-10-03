<?php
namespace universal;

class PraceSWebem extends BaseClass {

	const PARAMETR_HTML_PRO_XLS = 'xmlns:o="urn:schemas-microsoft-com:office:office"
									xmlns:x="urn:schemas-microsoft-com:office:excel"
									xmlns="http://www.w3.org/TR/REC-html40"';
	const PARAMETR_TABULKY_PRO_XLS = 'x:str';
	const DOCTYPE_STRICT = "<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Strict//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd'>";
	const DOCTYPE = self::DOCTYPE_STRICT;

	const XMLNS = 'xmlns="http://www.w3.org/1999/xhtml"';

	//konstanty typu jsou cislovany binarne pro zajisteni unikatnosti a kombinovatelnosti
	const JAVASCRIPT = 1;
	const TYPE_TEXT_JAVASCRIPT = 'type="text/javascript"';
	const ZNAKOVA_SADA = self::WINDOWS_1250;
	const WINDOWS_1250 = 'windows-1250';
	const CP_1250 = self::WINDOWS_1250;
	const UTF8 = 'UTF-8';
	const ISO_8859_2 = 'iso-8859-2';

	private $znakovaSada;
	private $jazyk;
	private $hlavicka;
	private $vypisKonecDokumentu = FALSE;
	private $vycentrujText = FALSE;

	public function __construct($znakovaSada = self::ZNAKOVA_SADA, $jazyk = 'cs', $vycentrujText = TRUE){
		$this->znakovaSada = self::dejZnakovouSadu($znakovaSada);
		$this->jazyk = $jazyk;
		$this->vycentrujText = $vycentrujText;
	}

	public function __destruct(){
		if($this->vypisKonecDokumentu){
			$this->zobrazHtmlKonec();
		}
	}

	public function dejXmlVersion(){
		$xmlVersion = "<?xml version='1.0' encoding='$this->znakovaSada' ?>";
		return $xmlVersion;
	}

	public static function dejUmisteniKlienta(){
		$umisteni = FALSE;
		$ip = $_SERVER['REMOTE_ADDR'];

		if (!empty($ip)) {
			$umisteni = file_get_contents("http://api.hostip.info/get_html.php?ip=$ip&position=TRUE");
		}
		return umisteni;
	}

	public static function vytvorTabulkuZDat($data,$zahlavi = array()){//data musi byt dvourozmerne pole
		$tabulka = "<table border=0 cellpadding=0 cellspacing=0 width=50%>\n";
		if(sizeof($zahlavi) > 0){//pokud jsme dostali nadpisy sloupcu
			$tabulka .= '<tr>';
			foreach($zahlavi as $nadpis){
				$tabulka .= "<td>$nadpis</td>";
			}
			$tabulka .= '</tr>';
		}
		foreach($data as $radek){
			$tabulka .= '<tr>';
			foreach($radek as $prvek){
				$tabulka .= "<td>$prvek</td>";
			}
			$tabulka .= '</tr>';
		}
		$tabulka .= '</table>';
		return $tabulka;
	}

	public function zobrazHtmlZacatek($hlavicka = FALSE, $nastavXml = FALSE
		,$parametryHtml = FALSE, $parametryBody = FALSE, $vycentrujText = TRUE
		,$priZruseniTridyVypsatKonecDokumetu = TRUE
	){
		$this->vycentrujText = $vycentrujText;
		$this->vypisKonecDokumentu = $priZruseniTridyVypsatKonecDokumetu;
		$dokument = '';
		if($nastavXml){
			$dokument .= $this->dejXmlVersion()."\n";
			$parametryHtml .= ' '.self::XMLNS." xml:lang='$this->jazyk'";
		}
		$dokument .= self::DOCTYPE."\n";
		$dokument .= "<html $parametryHtml lang='$this->jazyk'>\n";
		if($hlavicka !== FALSE){
			$dokument .= $hlavicka;
			$dokument .= "\t<body $parametryBody>\n";//nebudeme-li vypisovat hlavicku, nevypiseme ani tag body
			if($this->vycentrujText){
				$dokument .= "\t\t<div class='stredeni'>";
				$dokument .= "\n\t\t\t<div class='vystredeni'>\n";
			}
		}
		echo($dokument);
	}

	public function zobrazHtmlKonec(){
		$dokument = '';
		if($this->vycentrujText){
			$dokument .= "\n\t\t\t</div";
			$dokument .= "\n\t\t</div";
		}
		$dokument .= "\n\t</body>\n</html>";
		echo($dokument);
	}

	protected function vysliHeaderPresmerovani($cil,$cestaCileJeAbsoultni){
		$header = 'Location: ';
		if(!$cestaCileJeAbsoultni){
			$header .= self::getActualUrl(FALSE, FALSE);
		}
		$header .= $cil;
		header($header);
	}

	public function presmeruj($cil,$cestaCileJeAbsoultni = FALSE,$pojistka = TRUE){
		$header = 'HTTP/1.1 302 Found';//PHP Location bez dalsich uprav header vysle sice 302, ale s textem Not Found, coz je chybne
		header($header);
		$this->vysliHeaderPresmerovani($cil,$cestaCileJeAbsoultni);
		if($pojistka){
			exit;
		}
	}

	public function presmerujDocasne($cil,$cestaCileJeAbsoultni = FALSE,$pojistka = TRUE){
		$header = 'HTTP/1.1 307 Temporaly Redirect';
		header($header);
		$this->vysliHeaderPresmerovani($cil,$cestaCileJeAbsoultni);
		if($pojistka){
			exit;
		}
	}

	public function presmerujTrvale($cil,$cestaCileJeAbsoultni = FALSE,$pojistka = TRUE){
		$header = 'HTTP/1.1 301 Moved Permanently';
		header($header);
		$this->vysliHeaderPresmerovani($cil,$cestaCileJeAbsoultni);
		if($pojistka){
			exit;
		}
	}

	public static function zobrazSamooznacovaciText($text,$nazev,$nazevJeId = FALSE){
		if($nazevJeId){
			$funkce = 'setInputById';
		}else{
			$funkce = 'setInputByName';
		}
		$text = "<span onclick='$funkce(\"$nazev\");'>$text</span>";
		echo($text);
	}

	public static function prevedPoleNaGet($data){
		$a = FALSE;
		$get = '';
		foreach($data as $index=>$udaj){
			$index = htmlspecialchars($index);
			$udaj = htmlspecialchars((string)$udaj);
			$get .= ($a ? '&': '')."$index=$udaj";
			$a = TRUE;
		}
		return $get;
	}

	public static function zobrazDoctype(){
		echo(self::DOCTYPE."\n");
	}

	public static function jsemNaServeruHbi(){
		return $_SERVER['HTTP_HOST'] == 'web06.hbi.cz';
	}

	public static function jsemNaSvemPocitaci(){
		if(isset($_SERVER['SERVER_ADMIN']) and strpos($_SERVER['SERVER_ADMIN'],'tyc.j') === 0){//jestlize poustim skript z osobniho pocitace
			return TRUE;
		}else{
			return FALSE;
		}
	}

	public static function pripravExportDoSouboru($nazev,$pridatDatumKNazvu = FALSE,$typSouboru='txt'){
		if($pridatDatumKNazvu){
			$nazev .= date('_j_n_Y');
		}
		$nazev .= '.'.$typSouboru;
		header("Content-type: plain/text");
		header("Content-Disposition: attachment;filename=$nazev");
	}

	public static function exportujDoSouboru($data,$nazev,$pridatDatumKNazvu = FALSE,$typSouboru='txt'){
		self::pripravExportDoSouboru($nazev,$pridatDatumKNazvu,$typSouboru);
		echo($data);//vypiseme data pro export - hlavicky zajisti, ze obsah bude vlozen do souboru a nahran k uzivateli
	}

	public function exportujDoExcelu($zdrojDat, $nazevSouboru = 'export', $znakovaSada = FALSE){
		if($znakovaSada === FALSE){
			$znakovaSada = $this->znakovaSada;
		}
		$PraceSWebem = new PraceSWebem($znakovaSada);
		$PraceSWebem->pripravExportDoSouboru($nazevSouboru,TRUE,Nazvy::XLS);//pripravime prohlizec na prijeti souboru
		echo($PraceSWebem->zobrazHtmlZacatek(
			$hlavicka = $PraceSWebem->dejHlavicku('','',FALSE,FALSE,FALSE)
			,$nastavXml = FALSE, $parametryHtml = self::PARAMETR_HTML_PRO_XLS
			,$parametryBody = FALSE, $vycentrujText = FALSE));
		echo("<table ".PraceSWebem::PARAMETR_TABULKY_PRO_XLS." border=1 cellspacing=1 cellpading=1>\n");
		while($radek = fgets($zdrojDat)){
		echo($radek);
		}
		fclose($zdrojDat);
		echo("</table>\n");
	}

	public function dejHlavickuHtml($nadpis = '', $popis = '', $zobrazIkonu = TRUE){//opousteno
		return $this->dejHlavicku($nadpis, $popis, $zobrazIkonu);
	}

	public static function dejZnakovouSadu($znakovaSada){//pojistka pro pripady, kdy uzivatel zapomene pouzit format s pomlckou
		preg_match_all('#[[:alpha:]]+|[[:digit:]]+#',$znakovaSada,$htmlZnakovaSada);
		$htmlZnakovaSada = UtilitkyProPole::orezPole($htmlZnakovaSada);
		$htmlZnakovaSada = implode($htmlZnakovaSada,'-');
		return $htmlZnakovaSada;
	}

	public static function dejZvuk($umisteniZvuku, $vyska = 0, $sirka = 0, $autostart = TRUE){
		$zvuk = '';
		$umisteniZvuku = str_replace('\\','/',strtolower((string)$umisteniZvuku));
		/*if($autostart){
			$autostart = 'TRUE';
		}else{
			$autostart = 'FALSE';
		}*/
		if(!empty($umisteniZvuku)){
			//$typ = substr(strrchr($umisteniZvuku,'.'),1);
			/*if(strpos($_SERVER['HTTP_USER_AGENT'],'Opera') === 0){
				$zvuk .= "<embed src='$umisteniZvuku' controller='TRUE' autoplay='".(string)$autostart."' autostart='False' type='audio/wav' height='$vyska' width='$sirka'/>";
			}else{*/
				$zvuk .= "<embed src='$umisteniZvuku' type='application/x-mplayer2' autostart='".(int)$autostart."' playcount='1' height='$vyska' width='$sirka'>";
			//}
		}else{
			trigger_error("Cesta ke zvuku je prázdná",E_USER_WARNING);
		}
		return $zvuk;
	}

	public static function dejObjekt($umisteniObjektu, $vyska = 0, $sirka = 0, $autoplay = FALSE){
		$objekt = '';
		$umisteniObjektu = str_replace('\\','/',strtolower((string)$umisteniObjektu));
		if($autoplay){
			$autoplay = 'TRUE';
		}else{
			$autoplay = 'FALSE';
		}
		if(!empty($umisteniObjektu)){
			$typ = substr(strrchr($umisteniObjektu,'.'),1);
			switch($typ){
				case 'wav':
					$typ = 'audio/wav';
					break;
				default :
					trigger_error("Neznámý typ ($typ) objektu.",E_USER_WARNING);
			}
			if(preg_match("|^audio/|",$typ)){// type='$typ' data='$umisteniObjektu'
				$objekt .= "
					<object width='$sirka' height='$vyska'>
						<param name='autostart' value='$autoplay'>
						<param name='src' value='$umisteniObjektu'>
						<param name='autoplay' value='$autoplay'>
						<param name='controller' value='$autoplay'>
						alt : <a href='$umisteniObjektu'>".substr(strrchr($umisteniObjektu,'/'),1)."</a>
						<embed src='$umisteniObjektu' controller='TRUE' autoplay='$autoplay' autostart='$autoplay' type='$typ' />
					</object>";
			}else{
				trigger_error("Neznámý typ ($typ) objektu.",E_USER_WARNING);
			}
		}else{
			trigger_error("Cesta k objektu je prázdná",E_USER_WARNING);
		}
		return $objekt;
	}

	public function rozsirHlavicku($rozsireni,$druh = 0){
		if($druh){//mame pozadavek na typ polozky v hlavicce
			if(($druh & self::JAVASCRIPT) === self::JAVASCRIPT){//jestlize bitovy kod pro JAVASCRIPT je obsazen v pozadavku na druh rozsireni hlavicky, pridame tento druh do hlavicky
				$tag = 'script';
				$nastaveniTagu = self::TYPE_TEXT_JAVASCRIPT;
			}
		}
		if($druh !== 0){
			$this->hlavicka .= "\n<$tag $nastaveniTagu>";
		}
		$this->hlavicka .= $rozsireni;
		if($druh !== 0){
			$this->hlavicka .= "\n</$tag>";
		}
	}

	public function dejHlavicku($nadpis = '', $popis = '', $zobrazIkonu = TRUE, $beznyCss = TRUE, $beznyJs = TRUE){
		$this->hlavicka .= "\n\t\t<meta http-equiv='Content-Language' content='$this->jazyk'/>";
		$this->hlavicka .= "\n\t\t<meta http-equiv='Content-Type' content='text/html; charset=$this->znakovaSada'/>";
		/*$cestaVolanehoScriptu = dirname($_SERVER['PHP_SELF']);
		$htmlCestaKeTridam = str_replace(str_replace('\\','/',$_SERVER['DOCUMENT_ROOT']),'',str_replace('\\','/',dirname(__FILE__)));
		$htmlCestaKUniverzal = str_replace('\\','/',$_SERVER['HTTP_HOST']).str_replace('/Tridy','',$htmlCestaKeTridam);*/
		if($beznyCss){
			$mozneAdresareStylopisu = array('css','styles','styly');
			$mozneUmisteniAdresaru = array(
				dirname(__FILE__).'/../'=>'../univerzal/'
				,dirname($_SERVER['SCRIPT_FILENAME'])=>''
			);
			foreach($mozneUmisteniAdresaru as $souborovaCestaKAdresariStylopisu=>$htmlCestaKAdresariStylopisu){
				foreach($mozneAdresareStylopisu as $moznyAdresarStylopisu){
					$stylopisy = FolderTools::dejSouboryZAdresare($souborovaCestaKAdresariStylopisu."/$moznyAdresarStylopisu",'css');//vyhledame soubor se styly v univerzal
					if($stylopisy){
						foreach($stylopisy as $css){
							$this->hlavicka .= "\n\t\t<link rel='stylesheet' type='text/css' href='$htmlCestaKAdresariStylopisu$moznyAdresarStylopisu/$css' media='all'/>";
						}
					}
				}
			}
		}
		if($beznyJs){
			$mozneAdresare = array('js');
			$mozneUmisteniAdresaru = array(
				dirname(__FILE__).'/../'=>'../univerzal/'
				,dirname($_SERVER['SCRIPT_FILENAME'])=>''
			);
			foreach($mozneUmisteniAdresaru as $souborovaCestaKAdresari=>$htmlCestaKAdresari){
				foreach($mozneAdresare as $moznyAdresar){
					$javascripty = FolderTools::dejSouboryZAdresare($souborovaCestaKAdresari."/$moznyAdresar",'js');//vyhledame soubor se styly v univerzal
					if($javascripty){
						foreach($javascripty as $js){
							$this->hlavicka .= "\n\t\t<script type='text/javascript' src='$htmlCestaKAdresari$moznyAdresar/$js'></script>";
						}
					}
				}
			}
		}
		/*if($beznyJS){
			$this->hlavicka .= "\n\t\t<script type='text/javascript' src='js/main.js'></script>";
			foreach(FolderTools::dejSouboryZAdresare(dirname(__FILE__).'/../js','js') as $js){
				$this->hlavicka .= "\n\t\t<script type='text/javascript' src='".str_repeat('../',$this->dejPocetUrovniAdresaru())."univerzal/js/$js'></script>";
			}
		}*/
		if($zobrazIkonu and file_exists(self::$materskyAdresar.'/grafika/favicon.ico')){
			preg_match_all('|/[[:alnum:]]+$|',str_replace('\\','/',self::$materskyAdresar),$cesta);
			$cesta = UtilitkyProPole::orezPole($cesta);
		}else{
			$cesta = FALSE;
		}
		if($cesta !== FALSE){
			$this->hlavicka .= "\n\t\t<link rel='shortcut icon' href='$cesta/grafika/favicon.ico'/>";
		}
		$this->hlavicka .= "\n\t\t<title>$nadpis</title>";
		$this->hlavicka .= "\n\t\t<meta name='description' content='$popis'/>";
		$this->hlavicka = "\t<head>$this->hlavicka\n\t</head>\n";
		return $this->hlavicka;
	}

	public function dejPocetUrovniAdresaru($volaneAdresy = TRUE){
		if($volaneAdresy){
			$cesta = $_SERVER['REQUEST_URI'];
		}else{
			$cesta = $_SERVER['SCRIPT_NAME'];
		}
		$pocet = substr_count($cesta,'/')-1;
		return $pocet;
	}

	public static function getActualUrl($includeTrailingFile = TRUE, $includeParameters = TRUE, $includeProtocol = TRUE){
		if (isset($_SERVER['HTTP_HOST'])) {//pokud nepoustime skript mimo webovy prostor
			$server = $_SERVER['HTTP_HOST'];
			$adress = rtrim(dirname(str_replace('\\','/',$_SERVER['PHP_SELF'])), '/');//lomitka likvidujeme kvuli pozici v korenovem adresari, po kterem lomitko zustava
			$url = $server . $adress;
			if ($includeTrailingFile)
				$url .= $_SERVER['PHP_SELF'];
			if ($includeParameters)
				$url .= $_SERVER['QUERY_STRING'];
			if ($includeProtocol)
				$url = strtolower(strstr($_SERVER['SERVER_PROTOCOL'],'/',TRUE)) . '://' . $url;

			return $url;
		} else {
			throw new Exception('Url can not be determined out of web space', E_USER_WARNING);
		}
	}

	public function dejObsahSouboru($plnyNazevSouboru,$udajeVPromenneFiles = FALSE){
		$obsah = FolderTools::dejObsahSouboru($plnyNazevSouboru,$udajeVPromenneFiles = FALSE);
		$formatovanyObsah = '';
		foreach($obsah as $radek){
			$formatovanyObsah .= "$radek<br>\n";
		}
		return $formatovanyObsah;
	}

	public function dejUdajeZeSouboru($odstranPrazdno = TRUE, $ocekavanyPocet = -1, $zavaznostOdchylky = E_USER_ERROR){//vrati pole s hodnotami ze souboru, nactenych pri file upload
		$pozadavky = array();
		if(isset($_FILES)){//pokud je co
			foreach($_FILES as $nazevPozadavku=>$soubor){//kazdy soubor je popsan svym kodem a spoustu dalsich parametru
				if($_FILES[$nazevPozadavku]['tmp_name'] != ''){
					if(!$_FILES[$nazevPozadavku]['error']){
						if($_FILES[$nazevPozadavku]['size'] > 0){//pokud byl soubor skutecne prijat a neni prazdny
							$pozadavky[$nazevPozadavku] = FolderTools::dejObsahSouboru($_FILES[$nazevPozadavku]['tmp_name']);//vsechny radky ze souboru ulozime jako prvky pole s nazvem puvodniho kodu pozadavku
						}else{
							trigger_error("Soubor s identifikací '$nazevPozadavku' byl nahrán, ale má nulovou velikost! Bude ignorován.",E_USER_WARNING);
						}
					}else{
						trigger_error("Nahrání souboru s identifikací '$nazevPozadavku' se nezdařilo kvůli ".(implode(',',$_FILES[$nazevPozadavku]['error']))."! Soubor bude ignorován.",E_USER_WARNING);
					}
				}else{
					trigger_error("Nahraný soubor s identifikací '$nazevPozadavku' nemá název! Bude ignorován.",E_USER_WARNING);
				}
				//unset($_FILES[$nazevPozadavku]);//zlikvidujeme zaznam o souboru, at nas uz nikde neplete
			}
		}
		if(($ocekavanyPocet >= 0) and (sizeof($pozadavky) != $ocekavanyPocet)){
			trigger_error("Počet nahraných souborů ".((sizeof($pozadavky) > $ocekavanyPocet) ? 'přesáhl' : 'nedosáhl')." očekávanému počtu $ocekavanyPocet",$zavaznostOdchylky);
		}
		if($odstranPrazdno){
			$pozadavky = UtilitkyProPole::smazPrvkySPrazdnymiRetezci($pozadavky);
		}
		return $pozadavky;
	}
}