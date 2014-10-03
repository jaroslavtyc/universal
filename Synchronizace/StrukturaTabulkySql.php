<?php
	require_once(dirname(__FILE__).'/../Trida.php');

	class StrukturaTabulkySql extends Trida{
	
		protected $typSql;
		protected $nazevDatabaze;
		protected $nazevTabulky;
		protected $aliasTabulky;
		protected $sloupce = array();
		protected $vazba;
		
		const INNER_JOIN = 'JOIN';
		const LEFT_JOIN = 'LEFT JOIN';
		const RIGHT_JOIN = 'RIGHT JOIN';
		const NATURAL_JOIN = 'NATURAL JOIN';

		public function __construct($typSql,$nazevDatabaze = false,$nazevTabulky = false,$aliasTabulky = false){
			$this->nastavTypSql($typSql);
			$this->nastavNazevDatabaze($nazevDatabaze,false);
			$this->nastavNazevTabulky($nazevTabulky,false);
			$this->nastavAliasTabulky($aliasTabulky,false);
			parent::__construct();
			$this->zviditelniVlastnosti('typSql','nazevDatabaze','nazevTabulky','sloupce');
		}
		
		public function dejPolozky($plnyNazev = true, $vcetneAliasu = StrukturaSloupceSql::VCETNE_ALIASU){
			$polozky = array();
			foreach($this->sloupce as $sloupec){
				$polozka = $this->dejPolozku($sloupec,$plnyNazev, $vcetneAliasu);
				if($polozka !== false){
					$polozky[] = $polozka;
				}
			}
			return $polozky;
		}
		
		public function dejPolozku($sloupec, $plnyNazev = true, $vcetneAliasu = StrukturaSloupceSql::VCETNE_ALIASU){
			$polozka = false;
			if(!$sloupec->jenKOmezeni){
				if($plnyNazev and ($sloupec->typObjektu === StrukturaSloupceSql::TYP_OBJEKTU_NAZEV)){
					if($sloupec->dejZneni(StrukturaSloupceSql::JEN_ALIAS) !== false){
						$alias = ' AS '.$sloupec->dejZneni(StrukturaSloupceSql::JEN_ALIAS);
					}else{
						$alias = '';
					}
					$polozka = PraceSql::udelejPlnyNazevTabulky($this->nazevDatabaze,$this->nazevTabulky,$this->typSql).'.'.$sloupec->dejZneni(StrukturaSloupceSql::BEZ_ALIASU).$alias;
				}else{
					$polozka = $sloupec->dejZneni($vcetneAliasu);
				}
			}
			return $polozka;
		}
		
		public function dejSloupce($vcetneTechPouzeProOmezeni = false){//hole sloupce bez ohraniceni
			$sloupce = array();
			foreach($this->sloupce as $sloupec){
				if(!$sloupec->jenKOmezeni or $vcetneTechPouzeProOmezeni){
					$sloupce[] = $sloupec->dejVyslednyNazev(false);
				}
			}
			return $sloupce;
		}
		
		public function jeOmezeni(){
			foreach($this->sloupce as $sloupec){
				if($sloupec->jeOmezeni()){
					return true;
				}
			}
			return false;
		}
		
		public function dejOmezeni(){
			$zneniOmezeni = false;
			$oddeleni = false;
			foreach($this->sloupce as $sloupec){
				if($omezeniSloupce = $sloupec->dejOmezeni()){
					foreach($omezeniSloupce as $operator=>$skupinaJednohoOperatoruOmezeni){
						$carka = false;
						if($operator == 'IN'){
							$zneniOmezeni .= ($oddeleni ? "\n\tAND " : "\n\t").$this->dejVyslednyNazev().'.'.$sloupec->dejVyslednyNazev().' IN (';
						}
						foreach($skupinaJednohoOperatoruOmezeni as $typOmezeni=>$skupinaJednohoOperatoruOmezeni){
							foreach($skupinaJednohoOperatoruOmezeni as $omezeni){
								if($operator != 'IN'){
									$zneniOmezeni .= ($oddeleni ? "\n\tAND " : "\n\t").$this->dejVyslednyNazev().'.'.$sloupec->dejVyslednyNazev().' '.$operator;
								}else{
									$zneniOmezeni .= ($carka ? "\n\t," : "\n\t");
								}
								if($typOmezeni === StrukturaSloupceSql::TYP_OMEZENI_HODNOTA){
									$zneniOmezeni .= "'".PraceSql::predradUvozovky($omezeni)."'";
								}
								$oddeleni = true;
								$carka = true;
							}
						}
						if($operator == 'IN'){
							$zneniOmezeni .= ')';
						}
					}
				}
			}
			return $zneniOmezeni;
		}
		
		public function pridejSloupec($objekt,$typObjektu = StrukturaSloupceSql::TYP_OBJEKTU_NAZEV,$alias = false,$jenKOmezeni = false){
			$identifikator = (string)$objekt;
			if((string)$objekt === ''){
				if((string)$alias === ''){
					$identifikator = sizeof($this->sloupce);
				}else{
					$identifikator = (string)$alias;
				}
			}else{
				$identifikator = (string)$objekt;
			}	
			if(isset($this->sloupce[$identifikator])){
				trigger_error("Sloupec s tímto identifikátorem $identifikator již ve stuktuře tabulky máme, nelze ho přidat.",E_USER_NOTICE);
				return false;
			}else{
				$this->sloupce[$identifikator] = new StrukturaSloupceSql($this->typSql);
				$this->sloupce[$identifikator]->nastavZdroj($objekt,$typObjektu,$jenKOmezeni);
				if($alias !== false){
					$this->sloupce[$identifikator]->nastavAlias($alias);
				}
				return true;
			}
		}
		
		public function nastavOmezeniSloupce($sloupec,$operator,$omezeni,$typOmezeni = StrukturaSloupceSql::TYP_OMEZENI){
			if($sloupec = self::dejProverenyObjektSloupceTabulky($sloupec,$this,true)){
				return $sloupec->nastavOmezeni($operator,$omezeni,$typOmezeni);
			}else{
				return false;
			}
		}
		
		public function nastavJenKOmezeniSloupce($sloupec,$jenKOmezeni = true){
			if($sloupec = self::dejProverenyObjektSloupceTabulky($sloupec,$this,true)){
				return $sloupec->nastavJenKOmezeni($jenKOmezeni);
			}else{
				return false;
			}
		}
		
		public function nastavAliasSloupce($sloupec,$alias){
			if($sloupec = self::dejProverenyObjektSloupceTabulky($sloupec,$this,true)){
				return $sloupec->nastavAlias($alias);
			}else{
				return false;
			}
		}
		
		public function nastavTypObjektuSloupce($sloupec, $typObjektu){
			if($sloupec = self::dejProverenyObjektSloupceTabulky($sloupec,$this,true)){
				return $sloupec->nastavTypObjektu($tpObjektu);
			}else{
				return false;
			}
		}
		
		public function pridejVazbu($sloupecTetoTabulky,$vazebniTabulka,$sloupecVazebniTabulky = false,$typVazby = self::INNER_JOIN){
			$sloupecTetoTabulky = self::dejProverenyObjektSloupceTabulky($sloupecTetoTabulky,$this);
			if(!in_array($typVazby,self::dejTypyVazeb())){
				trigger_error("Dodaný typ vazby $typVazby není použitelným typem. Vazbu mezi tabulkami nelze sestavit.",E_USER_WARNING);
				return false;
			}
			if($sloupecVazebniTabulky === false and $typVazby === self::NATURAL_JOIN){
				$sloupecVazebniTabulky = $sloupecTetoTabulky->dejVyslednyNazev(false);//pri NATURAL_JOIN pouzijeme stejny nazev sloupce (POZOR NA ALIASY, muzou nadelat paseku)
			}
			if(!($sloupecVazebniTabulky = self::dejProverenyObjektSloupceTabulky($sloupecVazebniTabulky,$vazebniTabulka))){
				trigger_error("Nelze určit sloupec ".var_dump($sloupecVazebniTabulky)." vazební tabulky ".$vazebniTabulka->dejVyslednyNazev().". Vazbu mezi tabulkami nelze sestavit.",E_USER_WARNING);
				return false;
			}
			$this->vazba = array(
				'sloupecTetoTabulky'=>$sloupecTetoTabulky
				,'vazebniTabulka'=>$vazebniTabulka
				,'sloupecVazebniTabulky'=>$sloupecVazebniTabulky
				,'typVazby'=>$typVazby
			);
			return true;
		}
		
		public static function dejTypyVazeb(){
			return array(self::INNER_JOIN,self::LEFT_JOIN,self::RIGHT_JOIN,self::NATURAL_JOIN);
		}
		
		public static function dejProverenyObjektSloupceTabulky($sloupec,$tabulka,$alias = false){
			if(self::proverTabulku($tabulka)){
				if(is_object($sloupec)){
					if(is_a($sloupec,'StrukturaSloupceSql')){
						if(in_array($sloupec,$tabulka->sloupce)){
							return $sloupec;
						}else{
							trigger_error("Zadaný objekt sloupce tabulky ".$tabulka->dejVyslednyNazev()." udaný jako vazební (".$sloupec->dejVyslednyNazev().") není v její struktuře. Nejdíve ho do struktury přidej.",E_USER_WARNING);
							return false;
						}
					}else{
						trigger_error("Dodaný sloupec tabulky není objektem třídy StrukturaSloupceSql, ale ".get_class($sloupec).".",E_USER_WARNING);
						return false;
					}
				}else{
					$sloupec = (string)$sloupec;
					if(isset($tabulka->sloupce[$sloupec])){
						return $tabulka->sloupce[$sloupec];
					}elseif($alias !== false){
						if(is_string($alias)){//pokud alias je zadany nazev aliasu, ne jen pozadavek na hledani podle nazvu sloupce i v aliasech
							$sloupec = $alias;//budeme hledat podle aliasu, ne podle nazvu sloupce
						}
						foreach($tabulka->sloupce as $moznySpravnySloupec){
							if($moznySpravnySloupec->dejVyslednyNazev(false) == $sloupec){
								return $moznySpravnySloupec;
							}
						}
					}else{
						trigger_error("Sloupec tabulky ".$tabulka->dejVyslednyNazev()." udaný jako vazební ($sloupec) ve struktuře tabulky není.",E_USER_WARNING);
						return false;
					}
				}
			}else{
				return false;
			}
		}
		
		public static function proverTabulku($tabulka,$hlasChyby = true){
			if(!is_object($tabulka)){
				if($hlasChyby){
					trigger_error("Dodaná vazební tabulka (".(string)$tabulka.") musí být objektem třídy ".(__CLASS__).". Vazbu mezi tabulkami nelze sestavit.",E_USER_WARNING);
				}
				return false;
			}elseif(!is_a($tabulka,__CLASS__)){
				if($hlasChyby){
					trigger_error("Dodaná vazební tabulka není instancí třídy ".(__CLASS__).", ale ".(get_class($tabulka)).". Vazbu mezi tabulkami nelze sestavit.",E_USER_WARNING);
				}
				return false;
			}
			return true;
		}
		
		public static function dejProverenyTypSql($typSql){
			$typSql = (string)$typSql;
			foreach(self::dejTypySql() as $moznyTypSql){
				if(strcasecmp($typSql,$moznyTypSql)){
					return $typSql;
				}
			}
			return null;//behem cyklu hledani jsme nic nenasli a nevratili, vratime tedy null
		}
			
		public static function dejTypySql(){
			return array(
				PraceSql::MYSQL
				,PraceSql::MSSQL
			);
		}
		
		public function nastavTypSql($typSql, $hlasPrazdno = true){
			$typSql = self::dejProverenyTypSql($typSql);
			if(strlen($typSql) > 0){
				if(!isset($this->typSql)){
					$this->typSql = $typSql;
				}elseif($typSql !== $this->typSql){//nazev databaze uz nastaveny je, proverime, zda nemame pozadavek na jiny nazev databaze, nez ktery je uz nastaveny - nepripustne - blbuvzdornost
					trigger_error("Požadavek na nastavení typu SQL na $typSql nelze provést, typ databáze je již nastaven na $this->typSql.",E_USER_NOTICE);
				}//pokud je novy nazev databaze shodny s puvodnim, nic se nedeje
			}elseif($hlasPrazdno){
				trigger_error("Typ SQL k nastavení je prázdný.",E_USER_NOTICE);
			}
		}
		
		public function nastavNazevDatabaze($nazevDatabaze,$hlasPrazdno = true){
			$nazevDatabaze = (string)$nazevDatabaze;
			if(strlen($nazevDatabaze) > 0){
				if(!isset($this->nazevDatabaze)){
					$this->nazevDatabaze = $nazevDatabaze;
				}elseif($nazevDatabaze !== $this->nazevDatabaze){//nazev databaze uz nastaveny je, proverime, zda nemame pozadavek na jiny nazev databaze, nez ktery je uz nastaveny - nepripustne - blbuvzdornost
					trigger_error("Požadavek na nastavení názvu databáze na $nazevDatabaze nelze provést, název databáze je již nastaven na $this->nazevDatabaze.",E_USER_NOTICE);
				}//pokud je novy nazev databaze shodny s puvodnim, nic se nedeje
			}elseif($hlasPrazdno){
				trigger_error("Název databáze k nastavení je prázdný.",E_USER_NOTICE);
			}
		}
		
		public function nastavNazevTabulky($nazevTabulky,$hlasPrazdno = true){
			$nazevTabulky = (string)$nazevTabulky;
			if(strlen($nazevTabulky) > 0){
				if(!isset($this->nazevTabulky)){
					$this->nazevTabulky = $nazevTabulky;
				}elseif($nazevTabulky !== $this->nazevTabulky){//nazev databaze uz nastaveny je, proverime, zda nemame pozadavek na jiny nazev databaze, nez ktery je uz nastaveny - nepripustne - blbuvzdornost
					trigger_error("Požadavek na nastavení názvu tabulky na $nazevTabulky nelze provést, název tabulky je již nastaven na $this->nazevTabulky.",E_USER_NOTICE);
				}//pokud je novy nazev databaze shodny s puvodnim, nic se nedeje
			}elseif($hlasPrazdno){
				trigger_error("Název tabulky k nastavení je prázdný.",E_USER_NOTICE);
			}
		}
		
		public function nastavAliasTabulky($aliasTabulky,$hlasPrazdno = true){
			$aliasTabulky = (string)$aliasTabulky;
			if(strlen($aliasTabulky) > 0){
				if(!isset($this->aliasTabulky)){
					$this->aliasTabulky = $aliasTabulky;
				}elseif($aliasTabulky !== $this->aliasTabulky){//alias uz nastaveny je, proverime, zda nemame pozadavek na jiny, nez ktery je uz nastaveny - nepripustne - blbuvzdornost
					trigger_error("Požadavek na nastavení aliasu tabulky na $aliasTabulky nelze provést, název tabulky je již nastaven na $this->aliasTabulky.",E_USER_NOTICE);
				}//pokud je novy alias shodny s puvodnim, nic se nedeje
			}elseif($hlasPrazdno){
				trigger_error("Alias tabulky k nastavení je prázdný.",E_USER_NOTICE);
			}
		}
		
		public function dejVyslednyNazevSloupce($sloupec){
			if($sloupec = self::dejProverenyObjektSloupceTabulky($sloupec,$this)){
				return $this->dejVyslednyNazev().'.'.$sloupec->dejVyslednyNazev();
			}else{
				return false;
			}
		}
		
		public function dejVyslednyNazev($aliasNeboPuvodni = true){
			if($aliasNeboPuvodni and isset($this->aliasTabulky)){
				return PraceSql::ohranic($this->aliasTabulky,$this->typSql);
			}elseif(isset($this->nazevTabulky)){
				if(isset($this->nazevDatabaze)){
					$vyslednyNazev = PraceSql::udelejPlnyNazevTabulky($this->nazevDatabaze,$this->nazevTabulky,$this->typSql);
					if(!$aliasNeboPuvodni and isset($this->aliasTabulky)){
						$vyslednyNazev .= ' AS '.$this->aliasTabulky;
					}
					return $vyslednyNazev;
				}else{
					trigger_error("Chybí název databáze, výsledný název tabulky nelze sestavit",E_USER_NOTICE);
					return false;
				}
			}else{
				trigger_error("Výsledný název sloupce nelze sestavit, nemáme o sloupci dostatek údajů.",E_USER_NOTICE);
				return false;
			}
		}
		
		public function jeVazba(){
			return isset($this->vazba);
		}
		
		public function dejVazbu(){
			$vazba = $this->vazba['typVazby'].' '.$this->dejVyslednyNazev().' ON '.$this->dejVyslednyNazev().'.'.$this->vazba['sloupecTetoTabulky']->dejZneni().' = '.$this->vazba['vazebniTabulka']->dejVyslednyNazev().'.'.$this->vazba['sloupecVazebniTabulky']->dejZneni();
			return $vazba;
		}

	}
?>