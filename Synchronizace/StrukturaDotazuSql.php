<?php
	require_once(dirname(__FILE__).'/../Trida.php');

	class StrukturaDotazuSql extends Trida {
	
		protected $tabulky = array();
		protected $sloupce = array();//v pripade, ze jsou sloupce zadavane pres strukturu dotazu, nikoli primo do tabulky, ke ktere nalezi, budeme si jejich poradi uchovavat zde - muzeme tak mit poradi napric ruznymi tabulkami
	
		public function __construct(){
			parent::__construct();
			$this->pridejTabulky(func_get_args());
		}
		
		public function pridejTabulky(){
			if(sizeof(func_get_args()) > 0){
				foreach(func_get_args() as $moznaTabulka){
					$this->pridejTabulku($moznaTabulka);
				}
			}
		}
		
		public function pridejSloupec($tabulka,$sloupec,$typObjektu = StrukturaSloupceSql::TYP_OBJEKTU_NAZEV,$alias = false,$jenKOmezeni = false){//prida sloupec do tabulky a ulozi jeho nazev do razeneho seznamu, diky kteremu muzeme vyvolat sloupce ve stejnem poradi, v jakem byly zadavany a to napric tabulkami
			if($tabulka = $this->dejProverenyObjektTabulky($tabulka, true)){
				if($this->tabulky[$tabulka->dejVyslednyNazev()]->pridejSloupec($sloupec,$typObjektu,$alias,$jenKOmezeni)){
					$sloupec = StrukturaTabulkySql::dejProverenyObjektSloupceTabulky($sloupec,$tabulka,$alias);
					return $this->pridejSloupecDoSeznamu($tabulka->dejVyslednyNazev(),$sloupec->dejVyslednyNazev());
				}
			}else{
				return false;
			}
		}
		
		protected function pridejSloupecDoSeznamu($vyslednyNazevTabulky,$vyslednyNazevSloupce,$poradi = false,$opakovaneVlozeniJeChyba = true){
			if(!$poradi){
				$poradi = sizeof($this->sloupce)+1;
			}else{
				$poradi = (int)$poradi;
			}
			if(isset($this->sloupce[$poradi])){
				trigger_error("Sloupec s pořadím $poradi již v seznamu řazených sloupců je. Sloupec nebyl přidán.",E_USER_NOTICE);
				return false;
			}else{
				if($this->jeSloupecVRazenemSeznamu($vyslednyNazevTabulky,$vyslednyNazevSloupce)){
					if($opakovaneVlozeniJeChyba){
						trigger_error("Sloupec $vyslednyNazevSloupce pro tabulku $vyslednyNazevTabulky již v řazeném seznamu máme.",E_USER_NOTICE);
					}
					return false;
				}else{
					$this->sloupce[$poradi] = array(
						'tabulka'=>$vyslednyNazevTabulky
						,'sloupec'=>$vyslednyNazevSloupce
					);
					return true;
				}
			}
		}
		
		protected function jeSloupecVRazenemSeznamu($nazevTabulky,$nazevSloupce){
			foreach($this->sloupce as $poradi=>$sloupec){
				if(($sloupec['tabulka'] == $nazevTabulky) and ($sloupec['sloupec'] == $nazevSloupce)){
					return true;
				}
			}
			return false;
		}
			
		protected function dejProverenyObjektTabulky($tabulka,$hledejIVAliasech = false){
			$objekt = false;
			if(StrukturaTabulkySql::proverTabulku($tabulka,false)){
				if(in_array($tabulka,$this->tabulky)){
					$objekt = $tabulka;
				}
			}elseif($hledejIVAliasech){
				$tabulka = (string)$tabulka;
				if(isset($this->tabulky[$tabulka])){
					$objekt = $this->tabulky[$tabulka];
				}
			}
			if($objekt === false){
				trigger_error("Zadaná tabulka ".(string)$tabulka." není ve struktuře dotazu. Nejdíve jí do struktury přidej.",E_USER_WARNING);
			}
			return $objekt;
		}
		
		public function pridejTabulku(&$moznaTabulka){
			if(is_array($moznaTabulka)){
				foreach($moznaTabulka as $dalsiMoznaTabulka){
					$this->pridejTabulku($dalsiMoznaTabulka);
				}
			}elseif(is_object($moznaTabulka)){
				if(is_a($moznaTabulka,'StrukturaTabulkySql')){	
					if(!isset($this->tabulky[$moznaTabulka->dejVyslednyNazev()])){
						$this->tabulky[$moznaTabulka->dejVyslednyNazev()] = $moznaTabulka;
						return true;
					}else{
						trigger_error("Tato tabulka (".$moznaTabulka->dejVyslednyNazev().") už v seznamu je.",E_USER_NOTICE);
						return false;
					}
				}else{
					trigger_error("Předaná tabulka není instancí třídy StrukturaTabulkySql, ale ".get_class($moznaTabulka).". Nemůže být přidána do seznamu.",E_USER_WARNING);
					return false;
				}					
			}else{
				trigger_error("Předaná tabulka (".(string)$moznaTabulka.") není instance třídy StrukturaTabulkySql, nemůže být přidána do seznamu.",E_USER_WARNING);
				return false;
			}
		}
		
		public function dejPolozky(){
			$this->doplnVsechnySloupceDoSeznamuSerazenych();
			$polozky = false;
			$carka = false;
			foreach($this->sloupce as $poradi=>$sloupec){
				$tabulka = $this->dejProverenyObjektTabulky($sloupec['tabulka'],true);
				$sloupec = StrukturaTabulkySql::dejProverenyObjektSloupceTabulky($sloupec['sloupec'],$tabulka,true);
				$polozka = $tabulka->dejPolozku($sloupec);
				if($polozka !== false){
					$polozky .= ($carka ? "\n\t," : "\n\t").$polozka;
					$carka = true;
				}
			}
			return $polozky;
		}

		public function doplnVsechnySloupceDoSeznamuSerazenych(){
			foreach($this->tabulky as $nazevTabulky=>$tabulka){
				foreach($tabulka->dejSloupce() as $sloupec){
					$this->pridejSloupecDoSeznamu($nazevTabulky,$sloupec,false,false);//pridame vsechny dosud nezarazene sloupce do serazeneho seznamu
				}
			}
		}
		
		public function dejZdrojeAOmezeni(){
			if($this->proverASeradTabulkyPodleVazeb()){//alespon jedna tabulka musi byt bez vazeb, tedy bude prvni tabulkou ve FROM
				$zdrojeAOmezeni = '';
				$zdroje = false;
				$omezeni = false;
				$and = false;
				foreach($this->tabulky as $tabulka){
					if(!$tabulka->jeVazba()){
						/*if($zdroje === false){
							$zdroje = "\nFROM";
						}*/
						$zdroje .= "\n\t".$tabulka->dejVyslednyNazev(false);
					}else{
						$zdroje .= "\n\t".$tabulka->dejVazbu();
					}
					if($tabulka->jeOmezeni()){
						$omezeni .= ($and ? "\n\tAND " : "\nWHERE ").' ('.$tabulka->dejOmezeni().')';
						$and = true;
					}
				}
				if($zdroje){
					$zdrojeAOmezeni .= $zdroje;
				}
				if($omezeni){
					$zdrojeAOmezeni .= $omezeni;
				}
				return $zdrojeAOmezeni;
			}else{
				return false;
			}
		}
		
		private function proverASeradTabulkyPodleVazeb(){
			if(sizeof($this->tabulky) > 0){
				$tabulkySVazbou = array();
				$tabulkyBezVazby = array();
				foreach($this->tabulky as $tabulka){
					if($tabulka->jeVazba()){
						$tabulkySVazbou[] = $tabulka;
					}else{
						$tabulkyBezVazby[] = $tabulka;
					}
				}
				if(sizeof($tabulkyBezVazby) > 0){
					$this->tabulky = $tabulkyBezVazby;
					$this->tabulky = array_merge($this->tabulky, $tabulkySVazbou);
					return true;
				}else{
					trigger_error("Nemáme žádné tabulky bez vazeb.", E_USER_NOTICE);
					return false;
				}
			}else{
				trigger_error("Nemáme v seznamu žádnou tabulku.",E_USER_NOTICE);
				return false;
			}
		}
		
	}
?>