<?php
namespace universal;

require_once(dirname(__FILE__).'/../PraceSql/PraceMysql.php');

abstract class SynchronizaceSql extends PraceMysql{//trida Synchronizace je plne samostatna a nemusi byt abstract, ale vyuzivame v ni nazev tridy pro identifikaci te ktere synchronizace, musi byt proto unikatni

	private $minulaPosledniZmena;
	protected $omezeniKlicu;
	protected $noveUlozistePlnyNazev;
	protected $dotazy;
	private $nahrady;

	private $DavkoveNacitani;
	protected $StrukturaZdroje;
	protected $StrukturaCile;
	private $jenZmeny;
	private $nove;
	private $uprava;

	public function __construct($StrukturaZdroje, $StrukturaCile, $jenZmeny = true, $nove = true, $uprava = true){//vstupem jsou dve instance Struktury, ktere popisuji zdroj a cil. Zatim musi byt zdroj MSSQL a cil MySQL; bezne prevadime jen zmeny zdroje - pro ne vyzadujeme definovany sloupec s datem posledni zmeny ve zdroji i cili, pricemz v cili nesmi byt nahrada typu 'hodnota'! (soucast blbuvzdornosti); nove oznacuji, ze chceme ukladat data, pro ktera v cili jeste nemame hlavni klic; uprava, ze chceme menit radky s jiz existujicim klicem
		parent::__construct();
		if(!is_subclass_of($StrukturaZdroje,'StrukturaSql')){
			trigger_error('Dodaná struktura zdroje není třídou vhodného schématu, nelze pokračovat!',E_USER_ERROR);
			exit;
		}else{
			$this->StrukturaZdroje = $StrukturaZdroje;
		}
		if(!is_subclass_of($StrukturaCile,'StrukturaSql')){
			trigger_error('Dodaná struktura cíle není třídou vhodného schématu, nelze pokračovat!',E_USER_ERROR);
			exit;
		}else{
			$this->StrukturaCile = $StrukturaCile;
		}
		$this->jenZmeny = $jenZmeny;
		$this->nove = $nove;
		$this->uprava = $uprava;
		$this->Pripojeni = new $this->StrukturaCile->nazevPripojeni();//jde o pripojeni cile, pripojeni zdroje potrebujeme urcit jen jednou u Davkoveho nacitani
		$this->zajistiZdroje();//pripadne vytvori podle seznamu dotazu datove zdroje, napriklad pohledy
		$this->zajistiUloziste();// vytvori uloziste pro prenasena data
	}

	protected function zajistiZdroje(){//s databazi pracuje pouze synchronizace, ne struktura, proto pokud struktura vyzaduje vytvoreni zdroje, napriklad pohledu, preda tyto informace pomoci seznamu dotazu na zajisteni zdroju
		$Pripojeni = new $this->StrukturaZdroje->nazevPripojeni();
		foreach((array)$this->StrukturaZdroje->dotazyZdroju as $dotaz){
			$Pripojeni->provedDotaz($dotaz);
		}
	}

	protected function zajistiUloziste(){//vytvori vlastni tabulku pro ulozeni synchronizovanych dat, pokud jeste neexistuje
		$this->zajistiZakladniUloziste();//vytvori databazi a tabulku pro zaznam poslednich zmen, pokud dosud neexistuje
		if($this->StrukturaCile->zajistiTabulku){
			$dotaz = '
				CREATE TABLE IF NOT EXISTS
					'.PraceSql::ohranic($this->StrukturaCile->nazevTabulky,$this->StrukturaCile->typSql).'(';
			$carka = false;
			foreach($this->StrukturaCile->strukturaTabulky as $poradiSloupce=>$strukturaSloupce){
				if(!empty($strukturaSloupce['rozmerDatovehoFormatu'])){
					$strukturaSloupce['rozmerDatovehoFormatu'] = '('.$strukturaSloupce['rozmerDatovehoFormatu'].')';
				}
				$dotaz .= '
					'.($carka ? ',' : '').PraceSql::ohranic($strukturaSloupce['nazev'],$this->StrukturaCile->typSql).' '.$strukturaSloupce['datovyFormat'].$strukturaSloupce['rozmerDatovehoFormatu'].' '.$strukturaSloupce['dodatekDatovehoFormatu'];
				if($strukturaSloupce['index'] !== false){
					$dotaz .= '
						,';
					if(stripos($strukturaSloupce['index'],'PRIMARY') !== false){
						$dotaz .= 'PRIMARY KEY';
					}elseif(stripos($strukturaSloupce['index'],'UNIQUE') !== false){
						$dotaz .= 'UNIQUE';
					}else{
						$dotaz .= 'INDEX';
					}
					$dotaz .= '('.PraceSql::ohranic($strukturaSloupce['nazev'],$this->StrukturaCile->typSql).')';
				}
				$carka = true;
			}
			foreach($this->StrukturaCile->indexyPresSloupce as $index=>$indexPresSloupce){//pro kazdy pozadavek na index pres sloupce (pokud neni zadny, jde o prazdne pole a foreach neprobehne ani jednou)
				$dotaz .= ($carka ? '
					,' : '');
				if(stripos($indexPresSloupce['index'],'PRIMARY') !== false){
					$dotaz .= 'PRIMARY KEY';
				}elseif(stripos($indexPresSloupce['index'],'UNIQUE') !== false){
					$dotaz .= 'UNIQUE';
				}else{
					$dotaz .= 'INDEX';
				}
				$dotaz .= ' '.PraceSql::udelejNazevSloupce(implode($indexPresSloupce['sloupce'],' '));//vytvorime nazev indexu spojenim vsech nazvu sloupcu
				$dotaz .= ' (';
				$carka = false;
				foreach($indexPresSloupce['sloupce'] as $sloupec){
					$dotaz .= ($carka ? ',' : '').PraceSql::ohranic($sloupec,$this->StrukturaCile->typSql);//popiseme strukturu indexu vypsanim sloupcu v tabulce
					$carka = true;
				}
				$dotaz .= ')';
			}
			$dotaz .= ')';
			if($this->StrukturaCile->typSql == PraceMysql::MYSQL){
				$dotaz .= '
					ENGINE '.$this->StrukturaCile->engine;
			}
			$this->Pripojeni->provedDotaz($dotaz);
		}
	}

	/*private function dejPolozkyZdroje($nahradyZvlast = false){//ocekavame nazvy vsech polozek, ktere chceme ze zdroje nacitat
		$polozky = array();
		foreach($this->StrukturaZdroje->strukturaTabulky as $poradiPolozky=>$polozka){
			$nazev = PraceSql::ohranic($polozka['nazev'],$this->StrukturaZdroje->typSql);
			if($polozka['nahrada'] !== false){//u zdroje nahrada oznacuje upravu hodnot polozky jeste SQL serverem
				if(!isset($polozka['nahrada']['hodnota'])){
					trigger_error("Máme požadavek na náhradu zdroje, ale nemáme hodnotu náhrady!",E_USER_WARNING);
					exit;
				}
				switch(strtolower($polozka['nahrada']['typ'])){
					case 'udaj':
						$nahrada = $polozka['nahrada']['hodnota'];
						break;
					case 'sql':
						if(is_array($polozka['nahrada']['hodnota'])){
							if(sizeof($polozka['nahrada']['hodnota']) > 2){
								trigger_error("Uvedená náhrada je skládá z více než dvou částí - taková struktura náhrady není podporována",E_USER_WARNING);
								exit;
							}
							$premiera = true;
							$polozkaSNahradou = '';
							foreach($polozka['nahrada']['hodnota'] as $hodnota){
								$polozkaSNahradou .= " $hodnota";
								if($premiera){
									$polozkaSNahradou .= " $nazev";
									$premiera = false;
								}
							}
							$nahrada = $polozkaSNahradou;
						}else{
							$nahrada = "$nazev ".(string)$polozka['nahrada']['hodnota'];
						}
						break;
					default:
						$chyba = "Tento typ náhrady '".$strukturaPolozky['nahrada']['typ']."' pro zdroj neznám!";
						trigger_error($chyba,E_USER_ERROR);
				}
				if($nahradyZvlast){
					if(!isset($polozky['nahrady'])){
						$polozky['nahrady'] = array();
					}
					$polozky['nahrady'][$poradiPolozky] = $nahrada;
				}else{
					$nazev = $nahrada;
				}
			}
			$polozky[$poradiPolozky] = $nazev;
		}
		return $polozky;
	}*/

	protected function ulozDavku($davka){//vytahne z prijatych dat klice, pripadne upravi data podle pozadavku na nahradu, doda NULL na mista, kde je polozka cile uvedena jako 'nesynchronizovat'; podle klicu zjisti, zda je treba zaznam ulozit zcela novy, nebo upravit existujici
		//pokud kombinace klicu uz existuje, upravime prislusny zaznam, pokud ne, vytvorime novy zaznam
		$poziceKlicu = array_keys($this->StrukturaCile->klice);
		foreach($davka as $radek){
			$hodnotyKlicu = UtilitkyProPole::dejPrvekNaPozici($radek, $poziceKlicu, false);
			if(!empty($this->nahrady)){//jedine nahrady, ktere bereme v potaz pri ukladani, jsou funkce, ktere pouzijeme na prislusny prvek
				foreach($this->nahrady as $index=>$funkce){
					if(!isset($radek[$index])){
						trigger_error("Hodnota (pro funkci, jejíž výsledek je náhradou) na uvedené pozici '$index' neexistuje!");
						exit;
					}
					if(!method_exists($this, $funkce)){
						trigger_error("Funkce '$funkce' na úpravu hodnoty neexistuje!");
						exit;
					}
					$radek[$index] = call_user_func(array($this,$funkce),$radek[$index]);
				}
			}
			if($this->existujeRadek($hodnotyKlicu)){
				$this->upravRadek($radek,$hodnotyKlicu);
			}else{
				$this->ulozRadek($radek);
			}
		}
	}

	protected function existujeRadek($hodnotyKlicu){
		return ($this->dotazy['znameId']->provedPripravenyDotazSOrezem($hodnotyKlicu) and $this->uprava);
	}

	protected function upravRadek($radek,$hodnotyKlicu){
		if($this->uprava){
			$radek = array_diff_key($radek,$this->StrukturaCile->klice);//nemenime polozky,ktere jsou klici - POZOR, indexy klicu a polozek musi byt shodne
			$this->dotazy['uprava']->provedPripravenyDotaz(array_merge($radek,$hodnotyKlicu));
		}
	}

	protected function ulozRadek($radek){
		if($this->nove){
			$this->dotazy['nove']->provedPripravenyDotaz($radek);
		}
	}

	protected function pripravDotazyProUlozeniDat(){
		$this->naplnDotazyProUlozeniDat();
		foreach($this->dotazy as $druhCinnosti=>$dotaz){//pro kazdy dotaz vytvorime pripojeni s databazi - co instance to jeden pripraveny dotaz
			$this->dotazy[$druhCinnosti] = clone $this->Pripojeni;
			$this->dotazy[$druhCinnosti]->zmenDatabazi($this->StrukturaCile->nazevDatabaze);
			$this->dotazy[$druhCinnosti]->pripravDotaz($dotaz);
		}
	}

	protected function naplnDotazyProUlozeniDat(){
		$this->nahrady = array();//v nahradach je pripadny seznam funkci, ktere pouzijeme na hodnoty pred jejim ulozenim
		$statickeNahrady = array();//nahrady typu udaj a sql
		$this->dotazy = array();
		$this->dotazy['znameId'] = "
			SELECT COUNT(*) as pocet FROM
				".$this->StrukturaCile->plnyNazevTabulky."
			WHERE
				".$this->StrukturaCile->omezeniKlicu;
		$this->dotazy['uprava'] = "
			UPDATE
				".$this->StrukturaCile->plnyNazevTabulky."
			SET";
		$carkaUprava = false;
		$this->dotazy['nove'] = "
			INSERT INTO
				".$this->StrukturaCile->plnyNazevTabulky."(";
		$carkaNove = false;
		$polozekNove = 0;
		$posun = 0;//posun je dulezity kvuli pripadne nahrade typu 'funkce' - ta se pouzije az na ziva data, ktera jsou na urcite pozici, ovsem ziva data dostavame bez nahrad typu 'udaj' a 'sql', tedy musime pocitat s posunem hodnot oproti udane strukture (kazda nahrada jineho typu nez 'funkce' je ve strukture na miste, kde v zivych datech je uz dalsi hodnota)
		$poradi = 0;
		foreach($this->StrukturaCile->strukturaTabulky as $indexPolozky=>$strukturaPolozky){
			if(!$strukturaPolozky['nesynchronizovat']){//pokud stojime o synchronizaci, tedy ulozeni teto polozky
				if(!empty($strukturaPolozky['nahrada'])){//jestlize je polozky tyka nejaky druh nahrady
					switch(strtolower($strukturaPolozky['nahrada']['typ'])){//proverime typ nahrady a pripadne upravime ukladani podle nej
						case 'udaj'://holy udaj, respektive konstanta - neni nacitana ze zdroje, nybrz pridavana do dotazu zajistujicim ulozeni (napr. ?,?,'Main',?)
							$statickeNahrady[$polozekNove] = "'".$strukturaPolozky['nahrada']['hodnota']."'";//obalime hodnotu, kterou za chvili vsadime do dotazu
							$posun++;//ze zdroje tuto hodnotu nedostaneme, na toto misto dame konstantu, kterou zname predem - musime si poznamenat, ze na teto pozici zdroje bude uz nasledujici polozka cile
							break;
						case 'sql'://kod ve formatu SQL - pouzijeme ho stejne jako udaj, krome ohraniceni, ktere je u SQL kodu nesmyslne
							$statickeNahrady[$polozekNove] = $strukturaPolozky['nahrada']['hodnota'];
							$posun++;//ze zdroje tuto hodnotu nedostaneme, na toto misto dame konstantu, kterou zname predem - musime si poznamenat, ze na teto pozici zdroje bude uz nasledujici polozka cile
							break;
						default:
							$chyba = "Tento typ náhrady '".$strukturaPolozky['nahrada']['typ']."' neznám!";
							trigger_error($chyba,E_USER_ERROR);
					}
				}
				if(!in_array($strukturaPolozky['nazev'],$this->StrukturaCile->klice)){//pokud neni strukturaPolozky klicem a nebo je klic bez nahrazovane hodnoty (dulezite hlavne kvuli auto increment strukturaPolozkym, kde za 'nahrad' dosazujeme NULL)
					$this->dotazy['uprava'] .= '
						'.($carkaUprava ? ',' : '').PraceSql::ohranic($strukturaPolozky['nazev'],$this->StrukturaCile->typSql).'=';
					if(isset($statickeNahrady[$polozekNove])){
						$this->dotazy['uprava'] .= $statickeNahrady[$polozekNove];
					}elseif(isset($statickeNahrady[$polozekNove])){
						$this->dotazy['uprava'] .= $statickeNahrady[$polozekNove];
					}else{
						$this->dotazy['uprava'] .= '?';
					}
					$carkaUprava = true;
				}
				$this->dotazy['nove'] .=
					($carkaNove ? ',' : '').'`'.$strukturaPolozky['nazev'].'`';
				$carkaNove = true;
				$polozekNove++;
				$poradi++;
			}
		}
		$this->dotazy['nove'] .= '
			)
		VALUES(';
		$carkaNove = false;
		for($i = 0; $i < $polozekNove; $i++){
			$this->dotazy['nove'] .= ($carkaNove ? '
				,' : '');
			if(isset($statickeNahrady[$i])){
				$this->dotazy['nove'] .= $statickeNahrady[$i];
			}elseif(isset($statickeNahrady[$i])){
				$this->dotazy['nove'] .= $statickeNahrady[$i];
			}else{
				$this->dotazy['nove'] .= '?';
			}
			$carkaNove = true;
		}
		$this->dotazy['nove'] .= ')';
		$this->dotazy['uprava'] .= "
			WHERE
				".$this->StrukturaCile->omezeniKlicu;
		if(!$this->nove){
			unset($this->dotazy['nove']);
		}
		if(!$this->uprava){
			unset($this->dotazy['uprava']);
		}
	}

	private function dejZdrojeAOmezeni($StrukturaZdroje = false,$StrukturaCile = false){
		if(!$StrukturaZdroje){
			$StrukturaZdroje = &$this->StrukturaZdroje;
		}
		if(!$StrukturaCile){
			$StrukturaCile = &$this->StrukturaCile;
		}
		if(is_string($StrukturaZdroje->zdroj)){//zdroj je retezec, tedy nazev tabulky a my ho muzeme rovnou pouzit
			$zdroj = $StrukturaZdroje->zdroj;
		}elseif(is_object($StrukturaZdroje->zdroj)){//dostali jsme zdroj ve formatu objektu, proverime si objektu
			if(is_subclass_of($StrukturaZdroje->zdroj,'StrukturaSql')){//jde skutecne o instanci StrukturaSql, sestavime podle ni strukturu dotazu se zdrojovymi daty
				$puvodniZdroj = $this->{__FUNCTION__}($StrukturaZdroje->zdroj,$StrukturaCile);//zdroj sestavime podle struktury uvedene ve zdroji
				$zdroj = '(
					SELECT
					';
				$carka = false;
				foreach($StrukturaZdroje->dejPolozkyZdroje() as $polozka){
					$zdroj .= ($carka ? '
						,' : '').$polozka;
					$carka = true;
				}
				$zdroj .= "
					FROM
						$puvodniZdroj
					) AS ".$StrukturaZdroje->nazevTabulky;
			}else{
				triger_error("Dodaná třída (".get_class($StrukturaZdroje->zdroj).") se strukturou zdroje není platným potomkem třídy StrukturaSql, bez zdroje nelze pokračovat.",E_USER_ERROR);//tohle je zavazna chyba, protoze $this->StrukturaZdroje->zdroj musel uniknout kontrole ve tride StrukturaSql!
			}
		}else{
			triger_error("Chybný formát struktury zdroje (".gettype($StrukturaZdroje->zdroj)."). Bez zdroje nelze pokračovat.",E_USER_ERROR);//tohle je zavazna chyba, protoze $StrukturaZdroje->zdroj musel uniknout kontrole ve tride StrukturaSql!
		}
		//konvertujeme datum do znakoveho retezce, abychom ziskali cely casovy zaznam, vcetne milisekund
		$zdrojeAOmezeni = "\n\t$zdroj";
		if(($StrukturaZdroje->sloupcePosledniZmeny and !is_object($StrukturaZdroje->zdroj)) or !empty($StrukturaZdroje->omezeni)){
			$zdrojeAOmezeni .= "\nWHERE";
		}
		if($StrukturaZdroje->sloupcePosledniZmeny and !is_object($StrukturaZdroje->zdroj)){//jestlize mame sloupce posledni zmeny a tyto sloupce jsme dosud neomezovali v pripadnem vnorenem dotazu
			$or = false;
			$zdrojeAOmezeni .= "\n\t (";
			if(is_object($StrukturaZdroje->hlavniStrukturaZdroje)){//pokud struktura prave zpracovavaneho zdroje nese strukturu oznacenou jako hlavni, tedy napric vice strukturami je jedna vudci, pouzijeme pro hledani posledni zmeny prave tu
				$StrukturaHlavnihoZdroje = $StrukturaZdroje->hlavniStrukturaZdroje;
			}else{
				$StrukturaHlavnihoZdroje = $StrukturaZdroje;
			}
			foreach($StrukturaZdroje->sloupcePosledniZmeny as $sloupecPosledniZmeny){
				$zdrojeAOmezeni .= ($or ? "\n\t OR" : '').' '.PraceSql::ohranic($sloupecPosledniZmeny,$StrukturaZdroje->typSql).' >= ';
				if(($StrukturaZdroje->typSql == PraceMysql::MSSQL) and ($StrukturaCile->typSql == PraceMysql::MYSQL)){
					$zdrojeAOmezeni .= 'CONVERT(datetime,"'.$this->dejMinulouPosledniZmenu($StrukturaHlavnihoZdroje).'",20)';
				}elseif(($StrukturaZdroje->typSql == PraceMysql::MYSQL) and ($StrukturaCile->typSql == PraceMysql::MSSQL)){
					$zdrojeAOmezeni .= 'SUBSTRING("'.$this->dejMinulouPosledniZmenu($StrukturaHlavnihoZdroje).'",0,19)';
				}else{
					$zdrojeAOmezeni .= '"'.$this->dejMinulouPosledniZmenu($StrukturaHlavnihoZdroje).'"';
				}
				$or = true;
			}
			$zdrojeAOmezeni .= ')';
		}
		foreach($StrukturaZdroje->omezeni as $polozkaSOmezenim){
			$zdrojeAOmezeni .= "\n\t\tAND ".PraceSql::ohranic($polozkaSOmezenim['nazev'],$StrukturaZdroje->typSql).' '.$polozkaSOmezenim['operator'];
			switch(strtolower($polozkaSOmezenim['typ'])){
				case 'udaj':
					$zdrojeAOmezeni .= ' "'.$polozkaSOmezenim['hodnota'].'"';
					break;
				case 'sql':
					$zdrojeAOmezeni .= ' '.$polozkaSOmezenim['hodnota'];
					break;
				default:
					$chyba = "Tento typ omezení '".$polozkaSOmezenim['typ']."' neznám!";
					trigger_error($chyba,E_USER_ERROR);
			}
		}
		/*if($this->StrukturaZdroje->typSql == PraceMysql::MYSQL and (sizeof($this->StrukturaZdroje->sloupcePosledniZmeny) == 1)){
			$zdrojeAOmezeni .= '
				ORDER BY';
			$carka = false;
			foreach($this->StrukturaZdroje->sloupcePosledniZmeny as $sloupecPosledniZmeny){
				$zdrojeAOmezeni .= '
					'.($carka ? ',' : '').PraceSql::ohranic($sloupecPosledniZmeny,$this->StrukturaZdroje->typSql).' ASC';
				$carka = true;
			}
		}*/
		if($StrukturaZdroje->razeni){
			$zdrojeAOmezeni .= "\nORDER BY";
			$carka = false;
			foreach($StrukturaZdroje->razeni as $sloupec){
				$zdrojeAOmezeni .= "\n\t".($carka ? ',' : '').$sloupec['nazev'].' '.$sloupec['razeni'];
				$carka = true;
			}
		}
		if($StrukturaZdroje->seskupeni){
			$zdrojeAOmezeni .= "\nGROUP BY";
			$carka = false;
			foreach($StrukturaZdroje->seskupeni as $sloupec){
				$zdrojeAOmezeni .= "\n\t".($carka ? ',' : '').$sloupec['nazev'].($sloupec['razeni'] ? ' '.$sloupec['razeni'] : '');
				$carka = true;
			}
		}
		return $zdrojeAOmezeni;
	}

	private function pripravPrevod(){
		if($this->StrukturaZdroje->typSql == PraceSql::MYSQL){
			$this->DavkoveNacitani = new DavkoveNacitaniZMysql(new $this->StrukturaZdroje->nazevPripojeni(), $this->dejDotazProZdrojovaData(), 2000);
		}elseif($this->StrukturaZdroje->typSql == PraceSql::MSSQL){
			$razeni = array();
			if(is_array($this->StrukturaZdroje->sloupcePosledniZmeny) and (sizeof($this->StrukturaZdroje->sloupcePosledniZmeny) > 1)){
				trigger_error("Více sloupců poslední změny není při čtení ze zdroje MSSQL podporováno! Nelze pokračovat.",E_USER_ERROR);
			}
			$razeni = array($this->StrukturaZdroje->sloupcePosledniZmeny[0]=>'ASC');
			$this->DavkoveNacitani = new DavkoveNacitaniZMssql(new $this->StrukturaZdroje->nazevPripojeni(), $this->StrukturaZdroje->dejPolozkyZdroje(true), $this->dejZdrojeAOmezeni(), 2000, $razeni, false);
		}else{
			trigger_error('Neumíme pracovat s daným typem SQL ('.$this->StrukturaZdroje->typSql.')!',E_USER_ERROR);
			exit;
		}
		$this->pripravDotazyProUlozeniDat();
	}

	public function dejDotazProZdrojovaData($StrukturaZdroje = false){//davkove nacitani z mysql pouzije hotovy dotaz
		if(!$StrukturaZdroje){
			$StrukturaZdroje = $this->StrukturaZdroje;
		}
		if($StrukturaZdroje->typSql == PraceMysql::MYSQL){
			$dotaz = 'SELECT';
			$carka = false;
			foreach($StrukturaZdroje->dejPolozkyZdroje() as $polozka){
				$dotaz .= ($carka ? ',' : '')."\n\t$polozka";
				$carka = true;
			}
			$dotaz .= "\nFROM\n\t".$this->dejZdrojeAOmezeni($StrukturaZdroje);
			return $dotaz;
		}else{
			trigger_error('Tato funkce není pro daný typ SQL ('.$StrukturaZdroje->typSql.')!',E_USER_ERROR);
			exit;
		}
	}

	private function dejDavku(){
		//$davka = $this->DavkoveNacitani->dejDavku();
		return $this->DavkoveNacitani->dejDavku();
	}

	private function jeCoCist(){
		return $this->DavkoveNacitani->jeCoCist();
	}

	private function dejDruhPrace($StrukturaHlavnihoZdroje = false, $StrukturaCile = false){//nazev tridy zdrojove struktury + nazev tridy cilove struktury + nazev tridy synchronizace + plny nazev zdrojove tabulky + plny nazev cilove tabulky
		if(!$StrukturaHlavnihoZdroje){
			$StrukturaHlavnihoZdroje = $this->StrukturaZdroje;
		}
		if(!$StrukturaCile){
			$StrukturaCile = $this->StrukturaCile;
		}
		return get_class($StrukturaHlavnihoZdroje).'+'.get_class($StrukturaCile).'='.get_class($this).'=>'.$StrukturaHlavnihoZdroje->nazevDatabaze.'.'.$StrukturaHlavnihoZdroje->nazevTabulky.'->'.$StrukturaCile->nazevDatabaze.'.'.$StrukturaCile->nazevTabulky;
	}

	public function synchronizuj($ulozCasPosledniZmeny = true){
		if(!$this->nove and !$this->uprava){
			trigger_error("Byly zakázány veškeré úpravy pro přenos dat, spuštění přenosu nemá smysl",E_USER_NOTICE);
			exit;
		}
		$this->pripravPrevod();//pripravi natazeni dat a dotazy pro ulozeni (zjisteni existence zaznamu, ulozeni noveho nebo uprava existujicicho zaznamu)
		do{
			$this->ulozDavku($this->dejDavku());
		}while($this->jeCoCist());
		if($ulozCasPosledniZmeny){
			$this->ulozNovouPosledniZmenu();//ulozime cas nejnovejsi zmeny a pocet zmen s timto casem pro prave zpracovanou skupinu
		}
	}

	private function dejMinulouPosledniZmenu($StrukturaHlavnihoZdroje = false){
		if(!isset($this->minulaPosledniZmena)){
			$this->naplnMinulouPosledniZmenu($StrukturaHlavnihoZdroje);//ziskame cas nejnovejsiho zaznamu z minule synchronizace, ktery poslouzi k nalezeni novych zmen
		}
		return $this->minulaPosledniZmena;
	}

	private function ulozNovouPosledniZmenu($StrukturaHlavnihoZdroje = false){
		if(sizeof($this->StrukturaCile->sloupcePosledniZmeny) > 1){
			trigger_error("Ve struktuře <b>cíle</b> může být jen jeden sloupec poslední změny!",E_USER_ERROR);
		}else{
			$sloupecPosledniZmeny = $this->StrukturaCile->sloupcePosledniZmeny[0];
		}
		$dotaz = '
			SELECT
				MAX('.PraceSql::ohranic($sloupecPosledniZmeny,$this->StrukturaCile->typSql).')
			FROM
				'.$this->StrukturaCile->plnyNazevTabulky;
		$novaPosledniZmena = $this->Pripojeni->provedDotazSOrezem($dotaz);
		if(empty($novaPosledniZmena)){
			$novaPosledniZmena = PraceMysql::NULOVY_CAS;
		}
		$dotaz = "
			SELECT
				count(*)
			FROM
				".$this->StrukturaCile->plnyNazevTabulky."
			WHERE
				".PraceSql::ohranic($sloupecPosledniZmeny,$this->StrukturaCile->typSql)." = '$novaPosledniZmena'";
		$pocetNovychPoslednichZmen = $this->Pripojeni->provedDotazSOrezem($dotaz);
		$dotaz = '
			UPDATE
				'.PraceSql::ohranic('posledni_zmena_synchronizace',$this->StrukturaCile->typSql).'
			SET
				'.PraceSql::ohranic('PosledniZmena',$this->StrukturaCile->typSql)." = '$novaPosledniZmena'
				,".PraceSql::ohranic('PocetPoslednichZmen',$this->StrukturaCile->typSql)." = '$pocetNovychPoslednichZmen'
			WHERE
				".PraceSql::ohranic('DruhPrace',$this->StrukturaCile->typSql).' = "'.$this->dejDruhPrace($StrukturaHlavnihoZdroje).'"';
		$this->Pripojeni->provedDotaz($dotaz);
	}

	private function zajistiZakladniUloziste(){//zakladni uloziste se vzdy zajistuje pro cil, ne zdroj
		if($this->StrukturaCile->zajistiDatabazi){
			$dotaz = '
				CREATE DATABASE IF NOT EXISTS
					'.PraceSql::ohranic($this->StrukturaCile->nazevDatabaze,$this->StrukturaCile->typSql);
			if($this->StrukturaCile->typSql == PraceSql::MYSQL){
				$dotaz .= '
				DEFAULT CHARACTER SET '.$this->StrukturaCile->characterSet;
			}
			$this->Pripojeni->provedDotaz($dotaz);
		}

		$this->Pripojeni->zmenDatabazi($this->StrukturaCile->nazevDatabaze);

		$dotaz = '
			CREATE TABLE IF NOT EXISTS
				'.PraceSql::ohranic('posledni_zmena_synchronizace',$this->StrukturaCile->typSql).'(
					'.PraceSql::ohranic('DruhPrace',$this->StrukturaCile->typSql).' VARCHAR(250) PRIMARY KEY
					,'.PraceSql::ohranic('PocetPoslednichZmen',$this->StrukturaCile->typSql).' BIGINT UNSIGNED NOT NULL
					,'.PraceSql::ohranic('PosledniZmena',$this->StrukturaCile->typSql).' DATETIME DEFAULT "'.PraceSql::MINIMALNI_CAS.'"
				)';
		$this->Pripojeni->provedDotaz($dotaz);
	}

	private function naplnMinulouPosledniZmenu($StrukturaHlavnihoZdroje = false){
		if(!$StrukturaHlavnihoZdroje){
			$StrukturaHlavnihoZdroje = $this->StrukturaZdroje;
		}
		$dotaz = '
			INSERT IGNORE INTO
				'.PraceSql::udelejPlnyNazevTabulky($this->StrukturaCile->nazevDatabaze,'posledni_zmena_synchronizace',$this->StrukturaCile->typSql).'(
					'.PraceSql::ohranic('DruhPrace',$this->StrukturaCile->typSql).'
					,'.PraceSql::ohranic('PocetPoslednichZmen',$this->StrukturaCile->typSql).'
					,'.PraceSql::ohranic('PosledniZmena',$this->StrukturaCile->typSql).'
				)
			VALUES (
				"'.$this->dejDruhPrace($StrukturaHlavnihoZdroje).'"
				,0
				,"'.PraceMysql::MINIMALNI_CAS.'"
			)';
		$this->Pripojeni->provedDotaz($dotaz);

		//zmena data je dulezita pro kompatibilitu s MSSQL
		$dotaz = '
			SELECT';
		if($StrukturaHlavnihoZdroje->typSql == self::MSSQL){
			$dotaz .= '
				IF('.PraceSql::ohranic('PosledniZmena',$this->StrukturaCile->typSql).' < "'.PraceSql::MINIMALNI_CAS.'", "'.PraceSql::MINIMALNI_CAS.'", '.PraceSql::ohranic('PosledniZmena',$this->StrukturaCile->typSql).')';
		}else{
			$dotaz .= '
				'.PraceSql::ohranic('PosledniZmena',$this->StrukturaCile->typSql);
		}
		$dotaz .= '
			FROM
				'.PraceSql::udelejPlnyNazevTabulky($this->StrukturaCile->nazevDatabaze,'posledni_zmena_synchronizace',$this->StrukturaCile->typSql).'
			WHERE
				'.PraceSql::ohranic('DruhPrace',$this->StrukturaCile->typSql).' = "'.$this->dejDruhPrace($StrukturaHlavnihoZdroje).'"';
		$this->minulaPosledniZmena = $this->Pripojeni->provedDotazSOrezem($dotaz);
		if(empty($this->minulaPosledniZmena)){
			trigger_error("Minulá poslední změna zůstala prázdná, pravděpodobně chyba čtení z tabulky posledních změn! Bude použito nejstarší možné datum.",E_USER_WARNING);
			$this->minulaPosledniZmena = PraceSql::MINIMALNI_CAS;
		}
	}
}