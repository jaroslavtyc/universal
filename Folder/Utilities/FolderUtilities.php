<?php
namespace universal\FolderUtilities;

class FolderUtilities {

	const CSV_WITHOUT_HEADING = 0;
	const CSV_WITH_ORIGINAL_HEADING = 1;
	const CSV_WITH_MACHINE_HEADING = 2;
	const DEFAULT_DIRECTORY_SEPARATOR = '/';

	const FORMAT_POLE = 0;
	const FORMAT_TEXT_LINUX = 1;
	const FORMAT_TEXT_WINDOWS = 2;

	private function __construct(){}//static methods only


	/**
	* Replaces backslashes by slashes
	*
	* @param $path String path to standarize
	* @return String standarized path
	*/
	public static function makeStandarizedPath($path)
	{
		$path = str_replace('\\', '/', realpath($path));

		return $path;
	}

	/**
	* Replaces backslashes by slashes and adds trailing slash. Does not recognize trailing filename, always count path as pure dirpath
	*
	* @param $path String path to dir to standarize
	* @return String standarized dirpath
	*/
	public static function makeStandarizedDirpath($path)
	{
		if ($path !== '') {
			$path = self::makeStandarizedPath($path);
			if ($path[strlen($path)-1] != self::DEFAULT_DIRECTORY_SEPARATOR) {
				$path .= '/';
			}
		}

		return $path;
	}

	public static function winPrikazBezCekaniNaKonec($cmd,$priponaSouboru = false)
	{//nekontroluje priponu spusteneho souboru a vyzaduje plnou cestu
		if($priponaSouboru === false or is_file("$cmd.$priponaSouboru")){//pokud jsme dostali priponu souboru ke spusteni, proverime, zda existuje
			$WshShell = new COM('WScript.Shell');//instantizace windowsacke knihovny, ktera umoznuje spoustet programy
			$cmd = "cmd /C $cmd.$priponaSouboru";//cmd /C spusti prikazovy radek, ktery ocekavava za /C parametry v uvozovkach a po provedeni prikazu se zavre
			$stav = $WshShell->Run($cmd, 0, false);//spusteni programu pres prikazovy radek pomoci windowsacke knihovny(prvni parametr je program ke spusteni, druhy je kodove vyjadreni vyditelnosti spusteneho programu(0 je na pozadi), treti udava, zda cekat nebo necekat(false je necekat) na dokonceni programu) a zachyceni kodu chyby
			return (($stav == 0) ? true : false);//vraci priznak, zda se podarilo program spustit - pokud byl program spusten pres WshShell s tretim parametrem false(necekat na konec behu programu), vraci automaticky nulu
		}else

			return false;
	}

	public static function dejSouboryVZip($soubory,$pripona = false, $nazev = false,$adresar = false,$datumKNazvuExportu = false){
		$zip = new ZipArchive();
		$archiv = tempnam(sys_get_temp_dir(),'zip');
		if($zip->open($archiv,ZIPARCHIVE::CREATE) === true){
			$poradi = 1;
			foreach($soubory as $soubor){
				if($soubor){
					if(!is_array($soubor)){//pokud jsme dostali informace o zdroji ve slozenem formatu
						$soubor['cesta'] = $soubor;
					}
					if(!isset($soubor['nazev'])){
						if($nazev !== false){
							$soubor['nazev'] = (string)$nazev.$poradi;
						}else{
							$soubor['nazev'] = (string)$poradi;
						}
					}
					if($datumKNazvuExportu){
						$soubor['nazev'] .= date('_j_n_Y');
					}
					if(!isset($soubor['adresar'])){
						if($adresar !== false){
							$soubor['adresar'] = (string)$adresar;
						}else{
							$soubor['adresar'] = '';
						}
					}
					if(!isset($soubor['pripona'])){
						if($pripona !== false){
							$soubor['pripona'] = (string)$pripona;
						}else{
							$soubor['pripona'] = 'txt';
						}
					}
					if($soubor['adresar'] !== ''){
						$soubor['adresar'] = str_replace('\\','/',$soubor['adresar']);
						$soubor['adresar'] = UtilitkyProNazvy::srazOpakovanyZnak($soubor['adresar'],'/',1,true);
						if(strrpos($soubor['adresar'],'/') === (strlen($soubor['adresar'])-1)){//pokud posledni znak je /
							if(strlen($soubor['adresar']) === 1){//pokud cely nazev adresare tvorilo pouze lomitko, zrusime ho
								$soubor['adresar'] = '';
							}
						}else{
							$soubor['adresar'] .= '/';
						}
						if($soubor['adresar'] !== ''){
							$zip->addEmptyDir($soubor['adresar']);
						}
					}
					$zip->addFile($soubor['cesta'],$soubor['adresar'].$soubor['nazev'].'.'.$soubor['pripona']);
					if(isset($soubor['zdroj']) and is_resource($soubor['zdroj'])){
						fclose($soubor['zdroj']);
					}
				}
				$poradi++;
			}
			$zip->close();
		}
		/*foreach($soubory as $soubory){
			if(isset($soubor['zdroj']) and is_resource($soubor['zdroj'])){
				fclose($soubor['zdroj']);
			}
		}*/
		return $archiv;
	}

	public static function ukazSouboryVZip($soubory,$nazevExportu = false, $datumKNazvuExportu = true, $znakovaSada = PripojeniMysql::ZNAKOVA_SADA, $pripona = false, $nazev = false, $adresar = false, $datumKNazvuZipovanychSouboru = false){
		$archiv = self::dejSouboryVZip($soubory,$pripona,$nazev,$adresar,$datumKNazvuZipovanychSouboru);
		$PraceSWebem = new PraceSWebem($znakovaSada);
		if($nazevExportu === false){
			$nazevExportu = 'export';
		}
		$PraceSWebem->pripravExportDoSouboru($nazevExportu,$datumKNazvuExportu,'zip');//pripravime prohlizec na prijeti souboru
		$archiv = fopen($archiv,'r');
		while($radek = fgets($archiv)){
			echo($radek);
		}
		fclose($archiv);
	}

	public static function prectiIni($cesta){
		$obsahIni = array();
		$fileSource = fopen($cesta,'r');
		while($radek = fgets($fileSource)){
			$polozky = explode('=',$radek);
			$obsahIni[UtilitkyProNazvy::udelejNazevPromenne($polozky[0])] = trim($polozky[1]);
		}
		fclose($fileSource);

		return $obsahIni;
	}

	protected static function makeFileSource($cesta, $mod){
		if($odkaz = fopen($cesta, $mod)){
			return $odkaz;
		}else{
			trigger_error("NepodaĹ™ilo se otevĹ™Ă­t soubor s udanou cestou `$cesta`",E_USER_ERROR);
			exit;
		}
	}

	public static function readCsv($pathToCsv, $sJakymZahlavim = self::CSV_WITHOUT_HEADING, $propisovatHodnoty = false, $prazdnoNahrazovatNulou = false, $dataOhranicena = '"', $sloupceOddelene = ','){
		$prectenaData = array();
		$csv = fopen($pathToCsv, 'r');
		if($sJakymZahlavim != self::CSV_WITHOUT_HEADING){//pokud vubec zahlavi chceme
			if($nazvySloupcu = fgetcsv($csv,'"',',')){//nacteme prvni radek jako zahlavi
				foreach($nazvySloupcu as $index=>$nazevSloupce){//nactu prvni radek jako nazvy sloupcu
					switch($sJakymZahlavim){
						case self::CSV_WITH_ORIGINAL_HEADING :
							$nazvySloupcu[$index] = $nazevSloupce;
							break;
						case self::CSV_WITH_MACHINE_HEADING :
							$nazvySloupcu[$index] = UtilitkyProNazvy::udelejNazevPromenne($nazevSloupce);
							break;
						default:
							trigger_error("NeznĂˇmĂ˝ poĹľadavek na formĂˇt zĂˇhlavĂ­ (".(string)$sJakymZahlavim."). Bude pouĹľito nezmÄ›nÄ›nĂ© ze souboru.",E_USER_NOTICE);
							$nazvySloupcu[$index] = $nazevSloupce;
					}
				}
			}
		}
		$kodSkupinyPredchozihoRadku = '';
		while($radek = fgetcsv($csv,$dataOhranicena,$sloupceOddelene)){//dokud je ze souboru co cist
			if(isset($nazvySloupcu)){
				$radek = array_combine($nazvySloupcu, $radek); //nazvy sloupcu pouziji jako klice
			}
			if($propisovatHodnoty){//jestlize chceme na misto prazdnych udaju doplnit hodnoty stejneho sloupce predchoziho radku
				foreach($radek as $index=>$prvek){
					if($prvek === ''){//pokud je prvek radku prazdny
						if(isset($prectenaData[sizeof($prectenaData)-1][$index])){//pokud existoval tento prvek v predchozim radku
							$radek[$index] = $prectenaData[sizeof($prectenaData)-1][$index];//nahradime soucasne prazdno predchozim prvkem
						}
					}
				}
			}
			//prazdnoNahrazovatNulou musi byt az po propisovatHodnoty, v opacnem pripade bychom uz nemeli duvod (podminku) k propisovani
			if($prazdnoNahrazovatNulou){//jestlize chceme na misto prazdnych udaju doplnit nuly
				foreach($radek as $index=>$prvek){
					if($prvek === ''){//pokud je prvek radku prazdny
						$radek[$index] = 0;//nahradime soucasny prazdny retezec nulou
					}
				}
			}
			$prectenaData[] = $radek;
		}
		fclose($csv);

		return $prectenaData;
	}

	public static function readCsvWithHeading($pathToCsv, $machineNamesOfHeading = false){
		return self::readCsv($pathToCsv, ($machineNamesOfHeading ? 2 : 1));
	}

	public static function readCsvWithHeadingWriteThrough($pathToCsv, $machineNamesOfHeading = false){
		return self::readCsv($pathToCsv, ($machineNamesOfHeading ? 2 : 1), true);
	}

	public static function readCsvWithHeadingWriteThroughReplaceVoidByZero($pathToCsv, $machineNamesOfHeading = false){
		return self::readCsv($pathToCsv, ($machineNamesOfHeading ? 2 : 1), true, true);
	}

	public static function getFileContent($plnyNazevSouboru,$udajeVPromenneFiles = false,$format = self::FORMAT_POLE){
		if($udajeVPromenneFiles){
			$plnyNazevSouboru = $_FILES[$plnyNazevSouboru]['tmp_name'];
		}
		if($plnyNazevSouboru != ''){
			if($soubor = fopen($plnyNazevSouboru, 'r')){//pokud se ho zdarilo otevrit (coz u windowsackych temp souboru neni podle me zkusenosti vzdy zajisteno
				$limitPameti = UtilitkyProNazvy::udelejCisloZeZnakovehoZnaceni(ini_get('memory_limit'));
				$limitniUsekPameti = $limitPameti/10;
				$stredniLimitPameti = $limitPameti - $limitniUsekPameti;
				$dolniLimitPameti = $stredniLimitPameti - $limitniUsekPameti;
				$obsah = array();
				while($radek = fgets($soubor)){//nacteme soubor radek po radku
					$obsah[] = str_replace("\r\n",'',$radek);//co radek to prvek v poli - likvidujeme v nem odradkovani
					if(memory_get_usage() > $dolniLimitPameti){
						if(memory_get_usage() > $stredniLimitPameti){
							trigger_error("PĹ™i ÄŤtenĂ­ obsahu souboru '".basename($plnyNazevSouboru)."' jsme nebezpeÄŤnÄ› zatĂ­Ĺľili pamÄ›ĹĄ! MĹŻĹľe dojĂ­t k pĹ™edÄŤasnĂ©mu ukonÄŤenĂ­.",E_USER_WARNING);
						}else{
							trigger_error("PĹ™i ÄŤtenĂ­ obsahu souboru '".basename($plnyNazevSouboru)."' jsme znaÄŤnÄ› zatĂ­Ĺľili pamÄ›ĹĄ.",E_USER_NOTICE);
						}
					}
				}
				//rewind($soubor);
				fclose($soubor);//ukoncime komunikaci se souborem
				switch($format){
					case self::FORMAT_POLE://obsah souboru chceme v poli
						break;
					case self::FORMAT_TEXT_LINUX://obsah souboru chceme v jednom retezci, radky oddelene \n
						$obsah = implode("\n",$obsah);
						break;
					case self::FORMAT_TEXT_WINDOWS:
						$obsah = implode("\r\n",$obsah);
						break;
					default:
						trigger_error("NeznĂˇmĂ˝ vĂ˝stupnĂ­ formĂˇt pro obsah souboru, bude pouĹľit pĹ™ednastavenĂ˝ formĂˇt 'pole'.",E_USER_WARNING);
				}
				return $obsah;
			}else{
				trigger_error("Soubor '".basename($plnyNazevSouboru)."' se nepodaĹ™ilo pĹ™eÄŤĂ­st!",E_USER_WARNING);
				return false;
			}
		}else{
			trigger_error("NĂˇzev souboru je prĂˇzdnĂ˝!",E_USER_WARNING);
			return false;
		}
	}

	public static function getFilesFromDir($cestaKAdresari,$jenSouborySPriponou = false,$jenSouborySNazvem = '*'){
		if(file_exists($cestaKAdresari)){
			if(is_readable($cestaKAdresari)){
				$vypis = scandir($cestaKAdresari);
				$soubory = array();
				$cestaKAdresari = self::makeStandarizedDirpath($cestaKAdresari);
				if ($jenSouborySNazvem !== FALSE) {
					$jenSouborySNazvem = str_replace('*','.*',$jenSouborySNazvem);
					$jenSouborySNazvem = str_replace('?','.',$jenSouborySNazvem);
				}

				foreach($vypis as $slozka){
					if(is_file($cestaKAdresari . $slozka)){
						if(($jenSouborySPriponou === FALSE) ||
						  (preg_match('|'.(string)$jenSouborySNazvem.'[.]'.(string)$jenSouborySPriponou.'$|',$slozka))){
							$soubory[] = $slozka;
						}
					}
				}

				return $soubory;
			}else{

				return FALSE;
			}
		}else{

			return FALSE;
		}
	}

	public static function getDirsFromDir($dirPath, $includeCurrent = FALSE, $includeParent = FALSE){
		if (chdir($dirPath)) {
			$dirPath = getcwd();
			$vypis = scandir($dirPath);
			$dirs = array();
			foreach($vypis as $slozka){
				if(is_dir($dirPath."/$slozka")){
					if (($slozka != '.' || $includeCurrent) && ($slozka != '..' || $includeParent))
						$dirs[] = self::makeStandarizedDirpath($slozka);
				}
			}

			return $dirs;
		} else {
			return FALSE;
		}
	}

	/**
	 * Returns list of subdirectories of given directory, not recursively
	 *
	 * @param String $dirPath full path to directory to non-recursively scan subdirectories
	 * @return Array list of founded subdirectories
	 */
	public static function getSubdirectories($dirPath, $returnWithFullPath = FALSE, $recursive = FALSE){
		$subdirs = self::getDirsFromDir($dirPath, FALSE, FALSE);
		if ($returnWithFullPath) {
			if ($subdirs !== FALSE) {
				foreach($subdirs as &$subdir){
					$subdir = self::makeStandarizedDirpath(self::makeStandarizedDirpath($dirPath) . $subdir);
				}
				if ($recursive) {
					$subsubdirs = array();
					foreach($subdirs as &$subdir){
						$subsubdirs = array_merge($subsubdirs, self::getSubdirectories($subdir, $returnWithFullPath, $recursive));
					}
					$subdirs = array_merge($subdirs, $subsubdirs);
				}
			}
		} elseif ($recursive) {
			throw new Exception('List of recursively readed subdirectories has to contain full path of directories, otherways does not have sense', E_USER_WARNING);
		}

		return $subdirs;
	}

	public static function dejUdajeZNahranehoSouboru(){//vrati pole s hodnotami ze souboru, nacteneho pri file upload
		$pozadavky = array();
		if(isset($_FILES)){//pokud je co
			foreach($_FILES as $nazevPozadavku=>$soubor){//kazdy soubor je popsan svym kodem a spoustu dalsich parametru
				if($_FILES[$nazevPozadavku]['tmp_name'] != '' and !$_FILES[$nazevPozadavku]['error'] and $_FILES[$nazevPozadavku]['size'] > 0){//pokud byl soubor skutecne prijat a neni prazdny
					$pozadavky[$nazevPozadavku] = array();//vsechny radky ze souboru ulozime jako prvky pole s nazvem puvodniho kodu pozadavku
					if($soubor = fopen($_FILES[$nazevPozadavku]['tmp_name'], 'r')){//pokud se ho zdarilo otevrit (coz u windowsackych temp souboru neni podle me zkusenosti vzdy zajisteno
						while($radek = fgets($soubor)){//nacteme soubor radek po radku
							$pozadavky[$nazevPozadavku][] = str_replace("\r\n",'',$radek);//co radek to prvek v poli - likvidujeme v nem odradkovani
						}
						rewind($soubor);
						fclose($soubor);//ukoncime komunikaci se souborem
					}
				}
				//unset($_FILES[$nazevPozadavku]);//zlikvidujeme zaznam o souboru, at nas uz nikde neplete
			}
		}
		return UtilitkyProPole::smazPrvkySPrazdnymiRetezci($pozadavky);
	}

	public static function unlinkDir($fullName, $ifNotEmpty = FALSE)
	{
		if (!is_dir($fullName))
			throw new Exception('Given folder (' . $fullName . ')is not directory');
		else {
			$fullName = self::makeStandarizedDirpath($fullName);
			$files = self::getFilesFromDir($fullName);
			$dirs = self::getDirsFromDir($fullName);
			if (!$files && !$dirs)
				return rmdir($fullName);
			else {
				if (!$ifNotEmpty)
					return FALSE;
				else {
					foreach($files as $file){
						unlink($fullName . $file);
					}
					foreach($dirs as $dir){
						$functionName = __FUNCTION__;
						self::$functionName($fullName . $dir, $ifNotEmpty);
					}

					return rmdir($fullName);
				}
			}
		}
	}
}