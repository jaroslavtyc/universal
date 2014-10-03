<?php
	require_once(dirname(__FILE__).'/../Trida.php');

	class StrukturaSloupceSql extends Trida {
		
		protected $objekt;
		protected $typObjektu;
		protected $alias;
		protected $typSql;
		protected $omezeni;
		protected $datovyTyp;
		protected $rozmer;
		protected $jenKOmezeni;
		
		/*const NAHRADA_TYPU_HODNOTA = 1;//kolem hodnoty nahrady pridame uvozovky
		const NAHRADA_TYPU_SQL = 2;//nahrada je hotovou SQL strukturou, nebudeme ji jiz upravovat*/
		/*const VYZADOVANI_STRUKTURY = self::STRUKTURA_PRIPADNA;
		const STRUKTURA_ZADNA = 0
		const STRUKTURA_PRIPADNA = 1;
		const STRUKTURA_NUTNA = 2;*/
		
		/*const VYZADOVANI_NAHRADY = self::NAHRADA_PRIPADNA;
		const NAHRADA_ZADNA = 0;
		const NAHRADA_PRIPADNA = 1;*/
		
		const TYP_OBJEKTU = self::TYP_OBJEKTU_NAZEV;
		const TYP_OBJEKTU_NAZEV = 1;
		const TYP_OBJEKTU_HODNOTA = 2;
		const TYP_OBJEKTU_SQL = 3;
		
		const TYP_OMEZENI = self::TYP_OMEZENI_HODNOTA;
		const TYP_OMEZENI_HODNOTA = 1;
		const TYP_OMEZENI_SQL = 1;
		
		const BEZ_ALIASU = 0;
		const VCETNE_ALIASU = 1;
		const JEN_ALIAS = 2;
	
		public function __construct($typSql){
			$this->nastavTypSql($typSql);
			$this->zviditelniVlastnosti('objekt','typObjektu','alias','typSql','datovyTyp','rozmer','jenKOmezeni');
		}
		
		public function nastavOmezeni($operator,$omezeni,$typOmezeni = self::TYP_OMEZENI_HODNOTA){
			$operator = strtoupper($operator);
			if(in_array($typOmezeni,self::dejTypyOmezeni())){
				if(!isset($this->omezeni[$operator])){
					$this->omezeni[$operator] = array();
				}
				if(!isset($this->omezeni[$operator][$typOmezeni])){
					$this->omezeni[$operator][$typOmezeni] = array();
				}
				if(is_array($omezeni)){
					foreach($omezeni as $dalsiOmezeni){
						$this->nastavOmezeni($operator,$dalsiOmezeni,$typOmezeni);
					}
				}else{
					$this->omezeni[$operator][$typOmezeni][] = $omezeni;
				}
			}else{
				trigger_error("Neznámý druh omezení ($typOmezeni). Omezení nelze přidat.",E_USER_WARNING);
				return false;
			}
		}
		
		public function jeOmezeni(){
			return isset($this->omezeni);
		}
		
		public static function dejTypyOmezeni(){
			return array(self::TYP_OMEZENI_HODNOTA,self::TYP_OMEZENI_SQL);
		}
		
		public function dejOmezeni(){
			if(isset($this->omezeni)){
				return $this->omezeni;
			}else{
				return false;
			}
		}
		
		public function dejZneni($vcetneAliasu = self::VCETNE_ALIASU){
			$zneni = false;//schranka pro text zneni sloupce
			if(isset($this->objekt)){//je-li nastaveny nazev sloupce
				if(sizeof($this->objekt) > 0){//neni-li nazev sloupce prazdny
					if(isset($this->typObjektu)){
						if($vcetneAliasu !== self::JEN_ALIAS){
							switch($this->typObjektu){
								case self::TYP_OBJEKTU_HODNOTA:
									$zneni .= "'".PraceSql::predradUvozovky(str_replace('"',"'",$this->objekt))."'";
									break;
								case self::TYP_OBJEKTU_NAZEV:
									$zneni .= PraceSql::ohranic($this->objekt,$this->typSql);
									break;
								case self::TYP_OBJEKTU_SQL:
									$zneni .= $this->objekt;
									break;
								default:
									trigger_error("Neznámý typ objektu ".var_dump($this->typObjektu),E_USER_WARNING);
									return false;
							}
						}
						if($vcetneAliasu !== self::BEZ_ALIASU and isset($this->alias)){
							if($zneni !== false){
								$zneni .= ' AS ';
							}
							$zneni .= PraceSql::ohranic($this->alias,$this->typSql);
						}
					}else{
						trigger_error("Není nastaven typ objektu sloupce, nelze vrátit znění sloupce.",E_USER_WARNING);
						return false;
					}
				}else{
					trigger_error("Objekt sloupce je prázdný, nelze vrátit znění sloupce.",E_USER_WARNING);
					return false;
				}
			}else{
				trigger_error("Není nastaven objekt sloupce, nelze vrátit znění sloupce.",E_USER_WARNING);
				return false;
			}
			return $zneni;
		}
		
		public function dejVyslednyNazev($vcetneOhraniceni = true){
			if(isset($this->alias)){
				if($vcetneOhraniceni){
					return PraceSql::ohranic($this->alias,$this->typSql);
				}else{
					return $this->alias;
				}
			}elseif(isset($this->objekt)){
				if($vcetneOhraniceni){
					return PraceSql::ohranic($this->objekt,$this->typSql);
				}else{
					return $this->objekt;
				}
			}else{
				trigger_error("Výsledný název sloupce nelze sestavit, nemáme o sloupci dostatek údajů.",E_USER_NOTICE);
				return false;
			}
		}
		
		public function nastavCil($cil,$jenKOmezeni = false, $hlasPrazdno = true){
			$this->nastavZdroj($cil,self::TYP_OBJEKTU_NAZEV, $jenKOmezeni, $hlasPrazdno);
		}
		
		public function nastavZdroj($zdroj,$typObjektu = self::TYP_OBJEKTU, $jenKOmezeni = false, $hlasPrazdno = true){//je-li zdrojovy objekt typu hodnota, nebo SQL, jde vlastne o puvodni nahradu
			if(self::dejProverenyTypObjektu($typObjektu) === null){
				trigger_error("Dodaný typ objektu (".(string)$typObjektu.") není platný. Bude použit přednastavený typ ".self::TYP_OBJEKTU,E_USER_WARNING);
				$typObjektu = self::TYP_OBJEKTU;
			}
			$this->typObjektu = self::dejProverenyTypObjektu($typObjektu);
			$zdroj = (string)$zdroj;
			if(($this->typObjektu === self::TYP_OBJEKTU_HODNOTA) or (sizeof($zdroj) > 0)){
				$this->objekt = $zdroj;
			}else{
				switch($this->typObjektu){
					case self::TYP_OBJEKTU_NAZEV:
						$typ = 'název';
						break;
					case self::TYP_OBJEKTU_HODNOTA:
						$typ = 'hodnota';
						break;
					case self::TYP_OBJEKTU_SQL:
						$typ = 'sql';
						break;
				}
				trigger_error("Dodaný objekt sloupce typu '$typ' je prázdný. Objekt nelze nastavit.",E_USER_WARNING);
			}
			$this->jenKOmezeni = (bool)$jenKOmezeni;
		}
		
		public static function dejProverenyTypObjektu($typObjektu){
			if(in_array($typObjektu,self::dejTypyObjektu(),true)){
				return $typObjektu;
			}else{
				return null;
			}
		}
		
		public static function dejTypyObjektu(){
			return array(self::TYP_OBJEKTU_NAZEV,self::TYP_OBJEKTU_HODNOTA,self::TYP_OBJEKTU_SQL);
		}
		
		public function nastavAlias($alias,$hlasPrazdno = true){
			$alias = (string)$alias;
			if(strlen($alias) > 0){
				$this->alias = $alias;
				return true;
			}else{
				if($hlasPrazdno){
					trigger_error("Alias k nastavení je prázdný, nastavení nelze provést",E_USER_NOTICE);
				}
				return false;
			}
		}
		
		public function nastavTypObjektu($typObjektu){
			$typObjektu = $this->dejProverenyTypObjektu($typObjektu);
			if($typObjektu !== null){
				$this->typObjektu = $typObjektu;
				return true;
			}else{
				return false;
			}
		}
		
		public function nastavJenKOmezeni($jenKOmezeni){
			$this->jenKOmezeni = (bool)$jenKOmezeni;
			return true;
		}
			
		protected function nastavTypSql($typSql, $hlasPrazdno = true){
			if(!empty($typSql)){
				$this->typSql = StrukturaTabulkySql::dejProverenyTypSql($typSql);
				if(!isset($this->typSql)){
					trigger_error("Dodaný typ SQL ".(string)$typSql." nelze použít.",E_USER_WARNING);
				}
			}elseif($hlasPrazdno){
				trigger_error("Dodaný typ SQL pro sloupec je prázdný.",E_USER_WARNING);
			}
		}
	}
?>