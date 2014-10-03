<?php
	require_once(dirname(__FILE__).'/PripojeniSql.php');

	abstract class PripojeniMssql extends PripojeniSql{
	
		const PORT = 1433;
		
		protected $oddelovac;
		
		private $coInstanceTridyToInstancePripojeni;

		public function __construct($server, $uzivatel, $heslo, $databaze = null, $port = self::PORT){
			parent::__construct($server, $uzivatel, $heslo, $databaze, $port);
			$this->naplnOddelovacAdresyAPortu();
		}
		
		private function naplnOddelovacAdresyAPortu(){
			if(isset($_SERVER['WINDIR'])){
				$this->oddelovacAdresyAPortu = ',';
			}else{
				$this->oddelovacAdresyAPortu = ':';
			}
		}
		
		public function __destruct(){
			parent::__destruct();
		}
		
		public function pripojeni($novePripojeni = self::NOVE_PRIPOJENI){
			if(!$novePripojeni){//pokud nechceme nove pripojeni
				if(isset($this->pripojeni)){//a pripojeni opravdu existuje
					return $this->pripojeni;//vratime ho
				}else{//jestlize ovsem zadne pripojeni zatim nemame
					$this->pripojeni = $this->pripojeni(true);//zavolame funkci pripojeni znovu s parametrem noveho pripojeni na jeho vytvoreni
				}
			}else{//chceme nove pripojeni
				if($pripojeni = mssql_connect("$this->server$this->oddelovacAdresyAPortu$this->port", $this->uzivatel, $this->heslo, true)){
					if(!empty($this->databaze)){
						mssql_query("USE [$this->databaze]",$pripojeni);
					}
				}else{
					trigger_error($pripojeni->connect_error,E_USER_ERROR);
					$pripojeni = false;
				}
			}
			return $pripojeni;
		}
		
		public function dejVerzi(){
			$dotaz = "SELECT SERVERPROPERTY('productversion')";
			$verze = $this->provedDotazSOrezem($dotaz);
			return $verze;
		}

		/*protected function pripojSe(){
			return $this->pripojeni = $this->pripojeni();
		}*/
		
		public function zmenDatabazi($databaze){
			$this->databaze = $databaze;
		}
		
		public function ovlivnenoRadku(){
			return $this->ovlivnenoRadku;
		}
		
		public function odpojSe(){
			if(isset($this->pripojeni)){//null je pro nej non-set
				mssql_close($this->pripojeni);
				$this->pripojeni = null;
			}
		}
		
		public function provedDotaz($dotaz,$novePripojeni = self::NOVE_PRIPOJENI){
			$pripojeni = $this->pripojeni($novePripojeni);
			if($vysledek = mssql_query($dotaz,$pripojeni)){
				$this->ovlivnenoRadku = mssql_rows_affected($pripojeni);
				$vysledekPoRadcich = array();
				if(!is_bool($vysledek)){
					while($radek = mssql_fetch_row($vysledek)){
						$vysledekPoRadcich[]=$radek;
					}
				}else{
					$vysledekPoRadcich = $vysledek;
				}
				unset($pripojeni);
				return $vysledekPoRadcich;
			}else{
				$this->nahlasChybuDotazu($dotaz,$pripojeni->error);
				unset($pripojeni);
			}
		}
		
		private function nahlasChybuDotazu($dotaz,$chyba = ''){
			$zprava = "<br>
				na serveru:	<b>$this->server</b><br>
				".(empty($this->databaze) ? '' : "v databázi:	<b>$this->databaze</b><br>")."
				v dotazu: <b>$dotaz</b><br>
				";
			trigger_error($zprava.$chyba,E_USER_WARNING);//vypiseme zpravu o chybe
		}
		
		public final function vyprazdniTabulku($nazev){
			$this->provedDotaz("TRUNCATE TABLE [$nazev]");
		}

		public final function vymazDataZTabulky($nazev){//rychlejsi, ale neresetuje Id
			$this->provedDotaz("DELETE FROM [$nazev]");
		}
		
		protected function vyprazdniPametPoVysledku(){
			mssql_free_result($this->pripojeni);
		}
		
		public function posledniId(){}
		
		public function idPripojeni(){}
	}
?>