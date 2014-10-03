<?php
namespace universal;

abstract class PripojeniMysql extends PripojeniSql {

	protected $znakovaSada;
	protected $kompresePripojeni;
	protected $pripravenyDotaz;
	protected $vysledek;
	protected $udajeOSloupcichPoslednihoDotazu;
	protected $udajeOSloupcichPoslednihoPripravenehoDotazu;
	protected $posledniPripravenyDotaz;
	protected $zacatekPripravenehoDotazu;
	protected $trvaniPripravenehoDotazu;

	const PORT = 3306;
	const ZNAKOVA_SADA = 'cp1250';
	const UTF8 = 'utf8';
	const KOMPRESE_PRIPOJENI = 'MYSQLI_CLIENT_COMPRESS';

	public function __construct($server, $uzivatel, $heslo, $databaze = null, $port = self::PORT, $znakovaSada = self::UTF8, $kompresePripojeni = false){
		parent::__construct($server, $uzivatel, $heslo, $databaze, $port);
		$this->znakovaSada = $znakovaSada;
		$this->kompresePripojeni = $kompresePripojeni;
		$this->udajeOSloupcichPoslednihoDotazu = array();
		$this->udajeOSloupcichPoslednihoPripravenehoDotazu = array();
	}

	public function __destruct(){
		parent::__destruct();
	}

	protected function pripojeni($novePripojeni = self::NOVE_PRIPOJENI){
		if(!$novePripojeni){
			if(!isset($this->pripojeni)){
				$this->pripojeni = $this->pripojeni(true);
			}
			$pripojeni = $this->pripojeni;
		}else{
			$pripojeni = mysqli_init();
			if($pripojeni->real_connect($this->server, $this->uzivatel, $this->heslo, $this->databaze, $this->port, null, ($this->kompresePripojeni ? self::KOMPRESE_PRIPOJENI : null))){
				$pripojeni->query("SET NAMES $this->znakovaSada");
			}else{
				trigger_error($pripojeni->connect_error,E_USER_ERROR);
				$pripojeni = false;
			}
		}
		return $pripojeni;
	}

	public function dejVerzi(){
		$dotaz = "
			SELECT VERSION()";
		$verze = $this->provedDotazSOrezem($dotaz);
		preg_match_all("|^[.0-9]+|",$verze,$verze);
		$verze = UtilitkyProPole::orezPole($verze);
		return $verze;
	}

	public function provedDotazADejVysledekVTempuVeFormatuCsv($dotaz, $znakovaSada = 'cs1250', $nadpisy = true, $oddelovacSloupce = '"', $oddelovacRadku = ';', $adresar = false, $adresarVTempu = false){
		$pripojeni = $this->pripojeni();
		$pripojeni->query("SET character_set_results = $znakovaSada",MYSQLI_STORE_RESULT);
		$vysledek = $pripojeni->query($dotaz,MYSQLI_USE_RESULT);//dotaz se provede, ale vysledky zatim zustavaji na SQL serveru
		$this->posledniId = $pripojeni->insert_id;
		if(!$pripojeni->errno){
			unset($pripojeni);
			if(!is_bool($vysledek)){
				if(($radek = $vysledek->fetch_row()) and (sizeof($radek) > 0)){
					$this->udajeOSloupcichPoslednihoDotazu = $vysledek->fetch_fields();
					if(is_string($adresar)){
						if($adresarVTempu){
							$adresar = sys_get_temp_dir()."/$adresar";
						}
						if(!file_exists($adresar)){
							if(!mkdir($adresar)){
								trigger_error("Požadovaný adresář $adresar se nepodařilo vytvořit, bude použit běžný ".sys_get_temp_dir(),E_USER_WARNING);
								$adresar = sys_get_temp_dir();
							}
						}
					}else{
						$adresar = sys_get_temp_dir();
					}
					if(!($nazevSouboru = tempnam($adresar,'tmp'))){
						trigger_error("Nepodařilo se vygenerovat název pro dočasný soubor, nelze vrátit výsledek v tempu.",E_USER_WARNING);
						return false;
					}
					if(!($soubor = fopen($nazevSouboru,'w+'))){
						trigger_error("Nepodařilo se otevřít soubor $nazevSouboru pro zápis a čtení, nelze vrátit výsledek v tempu.",E_USER_WARNING);
						return false;
					}
					$poradiRadku = 0;
					if($nadpisy){
						$nadpisy = $this->dejNazvySloupcuPoslednihoDotazu();
						foreach($nadpisy as $nadpis){
							fwrite($soubor,$oddelovacSloupce.$nadpis['vyslednyNazev'].$oddelovacSloupce.$oddelovacRadku);
						}
						fwrite($soubor,"\n");
						$poradiRadku++;
					}
					do{
						foreach($radek as $udaj){
							fwrite($soubor,"$oddelovacSloupce$udaj$oddelovacSloupce$oddelovacRadku");
						}
						fwrite($soubor,"\n");
						$poradiRadku++;
					}while($radek = $vysledek->fetch_row());
					if($vysledek->num_rows > 0){
						rewind($soubor);
						$return = array('cesta'=>$nazevSouboru,'zdroj'=>$soubor, 'pocetRadku'=>$vysledek->num_rows);
					}else{
						fclose($soubor);
						$return = false;
					}
				}else{
					$return = false;
				}
				$vysledek->free_result();
			}else{
				$return = false;
			}
			return $return;
		}else{
			$this->nahlasChybuDotazu($dotaz,$pripojeni->error);
			unset($pripojeni);
		}
	}

	public function provedDotazADejVysledekVTempuVeFormatuPseudoExcelu($dotaz, $znakovaSada='cp1250', $jazyk='cs', $nadpisy = true, $barvyRadku = array()){
		//pouzitim dotazu ziskame pole s vysledky, ktere pretvorime na pseudo xml pouzitim html formatu a pripony xml a odesleme ke stazeni webovyn prohlizecem za pouziti heading
		$pripojeni = $this->pripojeni();
		$pripojeni->query("SET character_set_results = $znakovaSada");
		$vysledek = $pripojeni->query($dotaz,MYSQLI_USE_RESULT);//dotaz se provede, ale vysledky zatim zustavaji na SQL serveru
		$this->posledniId = $pripojeni->insert_id;
		if(!$pripojeni->errno){
			unset($pripojeni);
			if(!is_bool($vysledek)){
				if(($radek = $vysledek->fetch_row()) and sizeof($radek) > 0){
					$this->udajeOSloupcichPoslednihoDotazu = $vysledek->fetch_fields();
					$nazevSouboru = tempnam(sys_get_temp_dir(),'tmp');
					$soubor = fopen($nazevSouboru,'w+');
					$poradiRadku = 0;
					$PraceSWebem = new PraceSWebem(PraceSWebem::dejZnakovouSadu($znakovaSada));
					fwrite($soubor,'<html '.PraceSWebem::PARAMETR_HTML_PRO_XLS.">".$PraceSWebem->dejHlavickuHtml('','',false));
					fwrite($soubor,"<body>\n<table ".PraceSWebem::PARAMETR_TABULKY_PRO_XLS." border=1 cellspacing=1 cellpading=1>\n");
					if($nadpisy){
						$nadpisy = $this->dejNazvySloupcuPoslednihoDotazu();
						fwrite($soubor,'<tr'.(isset($barvyRadku[$poradiRadku]) ? ' BGCOLOR="'.$barvyRadku[$poradiRadku].'"' : '').'>');
						foreach($nadpisy as $nadpis){
							fwrite($soubor,"<td><b>".$nadpis['vyslednyNazev']."</b></td>");
						}
						fwrite($soubor,"</tr>\n");
						$poradiRadku++;
					}
					do{
						fwrite($soubor,'<tr'.(isset($barvyRadku[$poradiRadku]) ? ' BGCOLOR="'.$barvyRadku[$poradiRadku].'"' : '').'>');
						foreach($radek as $udaj){
							fwrite($soubor,"<td>$udaj</td>");
						}
						fwrite($soubor,"</tr>\n");
						$poradiRadku++;
					}while($radek = $vysledek->fetch_row());
					fwrite($soubor,"</table>\n</body>\n</html>");
					if($vysledek->num_rows > 0){
						rewind($soubor);
						$return = array('cesta'=>$nazevSouboru,'zdroj'=>$soubor, 'pocetRadku'=>$vysledek->num_rows);
					}else{
						fclose($soubor);
						$return = false;
					}
				}else{
					$return = false;
				}
				$vysledek->free();
			}else{
				$return = false;
			}
			return $return;
		}else{
			$this->nahlasChybuDotazu($dotaz,$pripojeni->error);
			unset($pripojeni);
		}
	}

	public function provedDotazADejVysledekVTempu($dotaz,$barvyRadku = array()){//pouzitim dotazu ziskame pole s vysledky, ktere pretvorime na pseudo xml pouzitim html formatu a pripony xml a odesleme ke stazeni webovyn prohlizecem za pouziti heading
		$pripojeni = $this->pripojeni();
		$vysledek = $pripojeni->query($dotaz,MYSQLI_USE_RESULT);//dotaz se provede, ale vysledky zatim zustavaji na SQL serveru
		$this->posledniId = $pripojeni->insert_id;
		if(!$pripojeni->errno){
			unset($pripojeni);
			if(($radek = $vysledek->fetch_row()) and sizeof($radek) > 0){
				$this->udajeOSloupcichPoslednihoDotazu = $vysledek->fetch_fields();
				$nadpisy = $this->dejNazvySloupcuPoslednihoDotazu();

				$tabulka = tmpfile();
				$poradiRadku = 0;
				fwrite($tabulka,'<tr'.(isset($barvyRadku[$poradiRadku]) ? ' BGCOLOR="'.$barvyRadku[$poradiRadku].'"' : '').'>');
				foreach($nadpisy as $nadpis){
					fwrite($tabulka,"<td><b>".$nadpis['vyslednyNazev']."</b></td>");
				}
				fwrite($tabulka,"</tr>\n");
				$poradiRadku++;
				do{
					fwrite($tabulka,'<tr'.(isset($barvyRadku[$poradiRadku]) ? ' BGCOLOR="'.$barvyRadku[$poradiRadku].'"' : '').'>');
					foreach($radek as $udaj){
						fwrite($tabulka,"<td>$udaj</td>");
					}
					fwrite($tabulka,"</tr>\n");
					$poradiRadku++;
				}while($radek = $vysledek->fetch_row());
				if($vysledek->num_rows > 0){
					rewind($tabulka);
					return array('zdroj'=>$tabulka, 'pocetRadku'=>$vysledek->num_rows);
				}else{
					fclose($tabulka);
					return false;
				}
			}else{
				return false;
			}
		}else{
			$this->nahlasChybuDotazu($dotaz,$pripojeni->error);
			unset($pripojeni);
		}
	}

	/*public function provedDotazADejVysledekVTempu($dotaz, $format = Nazvy::HTML){//pouzitim dotazu ziskame pole s vysledky, ktere pretvorime na pseudo xml pouzitim html formatu a pripony xml a odesleme ke stazeni webovyn prohlizecem za pouziti heading
		$pripojeni = $this->pripojeni();
		$vysledek = $pripojeni->query($dotaz,MYSQLI_USE_RESULT);//dotaz se provede, ale vysledky zatim zustavaji na SQL serveru
		$this->posledniId = $pripojeni->insert_id;
		$formatovaciZnaky = array(
			'zacatekRadku'=>array(
				Nazvy::HTML => '<tr>'
				,Nazvy::XLS => ''
			)
			,'konecRadku'=>array(
				Nazvy::HTML => "</tr>\n"
				,Nazvy::XLS => "\n"
			)
			,'zacatekBunky'=>array(
				Nazvy::HTML => '<td>'
				,Nazvy::XLS => ''
			)
			,'konecBunky'=>array(
				Nazvy::HTML => '</td>'
				,Nazvy::XLS => "\t"
			)
			,'zacatekNadpisu'=>array(
				Nazvy::HTML => '<td><b>'
				,Nazvy::XLS => ''
			)
			,'konecNadpisu'=>array(
				Nazvy::HTML => '</b></td>'
				,Nazvy::XLS => "\t"
			)
		);
		if(!$pripojeni->errno){
			unset($pripojeni);
			if(($radek = $vysledek->fetch_row()) and sizeof($radek) > 0){
				$this->udajeOSloupcichPoslednihoDotazu = $vysledek->fetch_fields();
				$nadpisy = $this->dejNazvySloupcuPoslednihoDotazu();

				$tabulka = tmpfile();
				fwrite($tabulka,$formatovaciZnaky['zacatekRadku'][$format]);
				foreach($nadpisy as $nadpis){
					fwrite($tabulka,$formatovaciZnaky['zacatekNadpisu'][$format].$nadpis['vyslednyNazev'].$formatovaciZnaky['konecNadpisu'][$format]);
				}
				fwrite($tabulka,$formatovaciZnaky['konecRadku'][$format]);
				do{
					fwrite($tabulka,$formatovaciZnaky['zacatekRadku'][$format]);
					foreach($radek as $udaj){
						fwrite($tabulka,$formatovaciZnaky['zacatekBunky'][$format].$udaj.$formatovaciZnaky['konecBunky'][$format]);
					}
					fwrite($tabulka,$formatovaciZnaky['konecRadku'][$format]);
				}while($radek = $vysledek->fetch_row());

				if($vysledek->num_rows > 0){
					rewind($tabulka);
					return array('zdroj'=>$tabulka, 'pocetRadku'=>$vysledek->num_rows);
				}else{
					fclose($tabulka);
					return false;
				}
			}else{
				return false;
			}
		}else{
			$this->nahlasChybuDotazu($dotaz,$pripojeni->error);
			unset($pripojeni);
		}
	}*/

	private function nahlasChybuDotazu($dotaz,$chyba = ''){
		$zprava = "<br>
			na serveru:	<b>$this->server</b><br>
			".(empty($this->databaze) ? '' : "v databázi:	<b>$this->databaze</b><br>")."
			v dotazu: <b>$dotaz</b><br>
			";
		echo("<br>\nChyba dotazu: ".$zprava.$chyba);
		//trigger_error($zprava,E_USER_WARNING);//vypiseme zpravu o chybe
	}

	public function dejUdajeOSloupcichPoslednihoDotazu(){
		return $this->udajeOSloupcichPoslednihoDotazu;
	}

	public function dejUdajeOSloupcichPoslednihoPripravenehoDotazu(){
		return $this->udajeOSloupcichPoslednihoPripravenehoDotazu;
	}

	public function dejNazvySloupcuPoslednihoPripravenehoDotazu(){
		$nazvy = array();
		foreach($this->udajeOSloupcichPoslednihoPripravenehoDotazu as $udaj){
			$nazvy[] = array(
				'puvodniNazev'=>$udaj->orgname
				,'vyslednyNazev'=>$udaj->name
			);
		}
		return $nazvy;
	}

	public function dejNazvySloupcuPoslednihoDotazu($puvodniNazev = true, $vyslednyNazev = true){//pokud uzivatel zada oba parametry false, dostane prazdne pole - zcela logicky a zcela nepouzitelne
		$nazvy = array();
		foreach($this->udajeOSloupcichPoslednihoDotazu as $udaj){
			if($puvodniNazev and $vyslednyNazev){
				$nazvy[] = array(
					'puvodniNazev'=>$udaj->orgname
					,'vyslednyNazev'=>$udaj->name
				);
			}elseif($puvodniNazev){
				$nazvy[] = $udaj->orgname;
			}elseif($vyslednyNazev){
				$nazvy[] = $udaj->name;
			}
		}
		return $nazvy;
	}

	protected function ulozCasZacatkuPripravenehoDotazu(){
		$this->zacatekPripravenehoDotazu = time();
	}

	protected function ulozTrvaniProvadeniPripravenehoDotazu(){
		$this->trvaniPripravenehoDotazu = (time() - $this->zacatekPripravenehoDotazu);
	}

	/*protected function pripojSe(){
		return $this->pripojeni = $this->pripojeni();
	}*/

	public function pripravDotaz($dotaz){
		if(!is_string($dotaz)){
			trigger_error("Dodaný dotaz k přípravě není řetězec, příprava není možná.",E_USER_WARNING);
			var_dump($dotaz);
		}
		$this->pripojeni(false);
		if(!$this->pripravenyDotaz = $this->pripojeni->prepare($dotaz)){
			$this->nahlasChybuDotazu($dotaz,$this->pripojeni->error);
			unset($this->pripojeni);
		}else{
			$this->posledniPripravenyDotaz = $dotaz;
		}
	}

	public function provedPripravenyDotazSOrezem($parametry = array(),$vysledekVcelku = true){
		return $this->provedPripravenyDotaz($parametry,$vysledekVcelku,true);
	}

	public function provedPripravenyDotaz($parametry = array(),$vysledekVcelku = true,$orezPrazdno = false){//pokud nechceme vsechna data z vysledku najednou, nastavime $vysledekVcelku na false a nechame tak data na serveru MySQL, dokud si je nevyzvedneme pomoci dejRadekVysledku() nebo dejZbytekVysledku()
		$parametry = (array)$parametry;
		if(sizeof($parametry) > 0){
			$this->priradParametry($parametry);
		}
		//$this->ulozCasZacatkuPripravenehoDotazu();
		if(!$this->pripravenyDotaz->execute()){
			if($this->pripravenyDotaz->errno == 1615){//Prepared statement needs to be re-prepared
				$this->pripravDotaz($this->posledniPripravenyDotaz);
				return $this->provedPripravenyDotaz($parametry,$vysledekVcelku,$orezPrazdno);
			}else{
				$this->nahlasChybuDotazu($this->posledniPripravenyDotaz.';parametry('.implode($parametry,'|').');errno='.$this->pripravenyDotaz->errno,$this->pripravenyDotaz->error);
			}
		}else{
			$this->posledniId = $this->pripravenyDotaz->insert_id;
			$this->ulozTrvaniProvadeniPripravenehoDotazu();
			/*if($this->trvaniPripravenehoDotazu > 1){
				echo("<br>
					pripraveny dotaz: $this->posledniPripravenyDotaz<br>
					trvani: $this->trvaniPripravenehoDotazu
				");
			}*/
			$vysledek = $this->spoctiVysledek($vysledekVcelku);
			if($orezPrazdno){
				return UtilitkyProPole::orezPole($vysledek);
			}else{
				return $vysledek;
			}
		}
	}

	private function spoctiVysledek($vysledekVcelku){
		if($informaceOVysledku = $this->pripravenyDotaz->result_metadata()){//pokud vubec nejaky vysledek je a my mame o cem informace ziskat
			$this->vysledek = array();//uloziste pro hodnoty ziskane dotazem
			for($i = 0; $i < $informaceOVysledku->field_count; $i++){
				$this->vysledek[] = &$$i;
			}
			$this->udajeOSloupcichPoslednihoPripravenehoDotazu = $informaceOVysledku->fetch_fields();
			$stavPrirazeniVysledku = call_user_func_array(array($this->pripravenyDotaz, 'bind_result'), $this->vysledek);//vysledne hodnoty navazeme na promenne, zachycene v seznamu $this->vysledek
			if($vysledekVcelku){
				$this->pripravenyDotaz->store_result();//natahnem si vsechna vysledna data ze serveru SQL
				return $this->dejZbytekVysledku();//zbytek z celeho dortu je cely dort
			}else{
				return $stavPrirazeniVysledku;//pokud jsme nechteli vratit nalezene hodnoty, vratime stav jak dopadlo zpracovani prirazeni - predpokladame ze vzdy dobre
			}
		}else{
			$this->udajeOSloupcichPoslednihoPripravenehoDotazu = array();
		}
	}

	public function dejRadekVysledku(){//v pripade, ze jsme nepredali vysledek v celku, muzeme ho davat po radcich
		if($this->pripravenyDotaz->fetch()){
			return UtilitkyProPole::dejKopiiPole($this->vysledek);
		}else{
			$this->vyprazdniPametPoVysledku();
			return false;
		}
	}

	protected function vyprazdniPametPoVysledku(){
		$this->pripravenyDotaz->free_result();
	}

	public function zrusPripravenyDotaz($ponechPripojeni = true){
		if(isset($this->pripravenyDotaz)){
			$this->pripravenyDotaz->close();
			$this->pripravenyDotaz = null;//nevyradime ho uplne jako pri unset, jen ho skryjeme pro isset;
		}
		if(!$ponechPripojeni){
			$this->odpojSe();
		}
	}

	public function dejZbytekVysledku(){//v pripade, ze jsme jeste nepredali vsechno, muzeme vratit zbyvajici cast vysledku
		$vysledek = array();
		while($radekVysledku = $this->dejRadekVysledku()){
			$vysledek[] = $radekVysledku;
		}
		return $vysledek;
	}

	private function priradParametry($parametry){//priradi parametry do pripraveneho dotazu
		array_unshift($parametry, $this->typy($parametry));//jako prvni polozku dame znakove vyjadreni typu promenych/parametru (i integer, s string atd)
		$referencedParameters = array();
		foreach($parametry as &$parametr)
			$referencedParameters[] = &$parametr;
		if(!call_user_func_array(array($this->pripravenyDotaz, 'bind_param'), $referencedParameters)){//a svazeme parametry s dotazem
			$this->nahlasChybuDotazu($this->posledniPripravenyDotaz.';'.implode($referencedParameters,'|').';errno='.$this->pripravenyDotaz->errno);
		}
	}

	private function typy($data){//vrati retezec se zastupmymi znaky pro stmt
		$typy = '';//sem budeme ukladat zastupne znaky typu
		foreach($data as $d){//rozebereme udaje na polozky
			if(is_int($d)){
				$typy .= 'i';
			}else{//pokud polozka nebyla ani z jednoho predchoziho typu, ulozime ji jako string (zejmena to plati pro null)
				$typy .= 's';
			}
		}
		return $typy;
	}

	public function ovlivnenoRadku(){
		return $this->pripojeni->affected_rows;
	}

	public function odpojSe(){
		if(isset($this->pripojeni)){
			$this->pripojeni->close();
			$this->pripojeni = null;
		}
	}

	public function zmenDatabazi($databaze){
		$this->provedDotaz("USE `$databaze`",false);//POZOR, pokud chceme zmenit databazi, nema smysl to delat na jinem nez stalem pripojeni - pouzitim teto funkce zmenDatabazi vytvorime stale pripojeni
		$this->databaze = $databaze;
	}

	public function predrad($data){
		if(is_string($data)){
			return($this->pripojeni()->real_escape_string($data));
		}else{
			return $data;
		}
	}

	public function provedDotaz($dotaz, $novePripojeni=self::NOVE_PRIPOJENI){
		$this->ulozCasZacatkuDotazu();
		$pripojeni = $this->pripojeni($novePripojeni);
		if($vysledek = $pripojeni->query($dotaz)){
			$this->ovlivnenoRadku = $pripojeni->affected_rows;
			$this->ulozTrvaniProvadeniDotazu();
			$this->posledniId = $pripojeni->insert_id;
			/*if($this->trvaniDotazu > 1){
				echo("<br>
					dotaz: $this->posledniDotaz<br>
					trvani: $this->trvaniDotazu
				");
			}*/
			if(!is_bool($vysledek)){
				$this->udajeOSloupcichPoslednihoDotazu = $vysledek->fetch_fields();
				$vysledekPoRadcich = array();
				while($radek = $vysledek->fetch_row()){
					$vysledekPoRadcich[]=$radek;
				}
				$konecnyVysledek = $vysledekPoRadcich;
			}else{
				$konecnyVysledek = $vysledek;//boolean
				$this->udajeOSloupcichPoslednihoDotazu = array();//vyprazdnime udaje o sloupcih
			}
			$this->posledniDotaz = $dotaz;
			unset($pripojeni);
			return $konecnyVysledek; //vrati pole nebo boolean
		}else{
			$this->nahlasChybuDotazu($dotaz,$pripojeni->error);
			unset($pripojeni);
		}
	}

	public final function vyprazdniTabulku($nazev){
		$this->provedDotaz("TRUNCATE TABLE `$nazev`");
	}

	public final function vymazDataZTabulky($nazev){//rychlejsi, ale neresetuje Id
		$this->provedDotaz("DELETE FROM `$nazev`");
	}

	public function posledniId(){
		return $this->posledniId;
	}

	public function idPripojeni($novePripojeni = self::NOVE_PRIPOJENI){
		return $this->provedDotazSOrezem('SELECT CONNECTION_ID()',false,$novePripojeni);
	}
}