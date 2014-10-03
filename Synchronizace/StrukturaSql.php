<?php
	require_once(dirname(__FILE__).'/../Trida.php');

	abstract class StrukturaSql extends Trida{
	
		const DATOVY_FORMAT = self::DATOVY_FORMAT_TEXT;
		const ROZMER_DATOVEHO_FORMATU = '';
		const DODATEK_DATOVEHO_FORMATU = '';
		const INDEX = false;
		const NAHRADA = false;
		const TYP_INDEXU = 'INDEX';
		const TYP_NAHRADY = 'udaj';
		const NESYNCHRONIZOVAT = false;
		const OMEZENI = false;
		const OPERATOR_OMEZENI = self::OPERATOR_OMEZENI_ROVNASE;
		const TYP_OMEZENI = self::TYP_OMEZENI_UDAJ;
		const ZAJISTI_ULOZISTE = true;
		const KLICE_VZTAH_AND = 'AND';
		const KLICE_VZTAH_OR = 'OR';
		
		const DATOVY_FORMAT_TEXT = 'TEXT';
		
		const RAZENI_SESKUPENI = '';//razeni pri GROUP BY nechame pro bezne nastaveni prazdne/bez razeni
		
		const RAZENI = self::RAZENI_SESTUPNE;
		const RAZENI_SESTUPNE = 'ASC';
		const RAZENI_VZESTUPNE = 'DESC';
		
		const TYP_OMEZENI_UDAJ = 'udaj';
		const OPERATOR_OMEZENI_ROVNASE = '=';
	
		//promenne, ktere musime naplnit v dcerine tride
		protected $typSql;//zda je struktura pro MySQL, nebo MSSQL...
		protected $nazevPripojeni;//nazev pripojeni, ktery je pouzit pro spojeni se serverem, obsahujicim tabulku
		protected $nazevDatabaze;//nazev databaze, ve ktere tabulka lezi	
		protected $tabulka;//samotna struktura tabulky, neni oznacena jako k videni, pouziva se jako zakladni zdroj informaci o tabulce

		protected $strukturaTabulky;//samotna struktura tabulky - ziskame ji proverenim a upravou stuktury, ziskane z promenne tabulka
		protected $nazevTabulky;//nazev tabulky, vytazeny ze struktury tabulky
		protected $plnyNazevTabulky;//nazev tabulky vcetne databaze, vytazeny ze struktury tabulky
		protected $zdroj;//zdroj dat, jde-li o strukturu zdroje - je-li zdrojem sama tabulka, je shodny s plnym nazvem tabulky, jde-li o komplikovanejsi strukturu, jde o zneni teto struktury v zavorkach a aliasem teto struktury/vyberu, ziskane z tridy StrukturaSql, ktera je v tabulka->
		protected $nazvySloupcu;//nazvy sloupcu, vytazene ze struktury tabulky
		protected $nazvySloupcuProSynchronizaci;//nazvy sloupcu, jejichz data skutecne chceme prevadet, vytazene ze struktury tabulky
		protected $klice;//polozky pro klic tabulky, vytazene ze struktury tabulky
		protected $vztahKlicu;//zda staci k urceni zaznamu jeden klic (kazdy je unikatni) ci musi byt jejich kombinace (unikatni jsou jen spolu)
		protected $omezeniKlicu;//cast predpripraveneho SQL dotazu pro WHERE s vyjmenovanymi klici a k nim ocekavane hodnote =?
		protected $indexyPresSloupce;//cast predpripraveneho SQL dotazu pro WHERE s vyjmenovanymi klici a k nim ocekavane hodnote =?
		protected $pozicePosledniZmeny;//pozice sloupce, ktery nese udaj o case posledni zmeny zaznamu
		protected $sloupcePosledniZmeny;//pozice sloupcu, ktere nesou udaj o case posledni zmeny zaznamu
		protected $engine;//engine databaze
		protected $characterSet;//znakova sada tabulky, vytazena ze struktury
		protected $omezeni;//pripadne omezeni jednotlivych polozek, promita se do WHERE...
		protected $zajistiUloziste;//zda chceme zkouset vytvaret uloziste na zaklade udaju o strukture cilove tabulky
		protected $zajistiDatabazi;//zda chceme zkouset vytvaret uloziste-databaze na zaklade udaju o strukture cilove tabulky
		protected $zajistiTabulku;//zda chceme zkouset vytvaret uloziste-tabulka na zaklade udaju o strukture cilove tabulky
		protected $dotazyZdroju;//pokud soucasti struktury zdroje jsou datove objekty, ktere vyzadujeme vytvaret/proverovat pri kazdem spousteni synchronizace, dame dotazy na zajisteni techto zdroju do teto promenne
		protected $razeni;//seznam sloupcu, podle kterych je vysledny vyber razen
		protected $seskupeni;//seznam sloupcu, podle kterych je vysledny vyber razen
		protected $hlavniStrukturaZdroje = false;//pro situace, kdy je zdroj slozen z vice StrukturSql, je treba urcit a uchovat hlavni strukturu, podle ktere se odviji zejmena cas posledni zmeny

		public function __construct(){
			parent::__construct();
			$this->zviditelniVlastnosti('nazevPripojeni','nazevDatabaze','strukturaTabulky','nazevTabulky','plnyNazevTabulky','nazvySloupcu','nazvySloupcuProSynchronizaci','klice','vztahKlicu','omezeniKlicu','indexyPresSloupce','pozicePosledniZmeny','sloupcePosledniZmeny','engine','characterSet','typSql','omezeni','zajistiUloziste','zajistiDatabazi','zajistiTabulku','dotazyZdroju','razeni','seskupeni','zdroj','hlavniStrukturaZdroje');//nezviditelnujeme tabulku, tu chceme pouze naplnit od dcerine tridy, uzivatelum teto tridy budeme predkladat jeji overene casti
			$this->naplnTypSql();
			$this->naplnNazevPripojeni();
			$this->naplnNazevDatabaze();
			$this->naplnTabulka();//nejen nazev, ale "kompletni" struktura tabulky
			$this->naplnStrukturaTabulky();//obsahuje spoustu kontrol
			$this->naplnZdroj();
		}
		
		protected abstract function naplnTypSql();//kazda strukura musi o sobe vedet, zda je MSSQL, MYSQL...
		
		protected abstract function naplnNazevPripojeni();//timto pripojenim budeme data cist, jde-li o strukturu zdroje, nebo zapisovat, jde-li o strukturu cile
		
		protected abstract function naplnNazevDatabaze();//v teto databazi tabulka lezi
				
		protected abstract function naplnTabulka();//strukura tabulky, od nazvu tabulky po strukturu jednotlivych sloupcu
			/*array(
				'nazev'=>
				,'klice'=>
				,'pozicePosledniZmeny'=>
				,'characterSet'=>
				,'indexyPresSloupce'=>array(
					'index'=>
					,'sloupce'=>array()
				)
				,'razeni'=>array(//ORDER BY
					'nazev'=>
					,'razeni'=>
				)
				,'seskupeni'=>array(//GROUP BY
					'nazev'=>
					,'razeni'=>
				)
				,'struktura'=>array(
					array(
						'nazev'=>
						,'datovyFormat'=>
						,'rozmerDatovehoFormatu'=>
						,'dodatekDatovehoFormatu'=>
						,'index'=>
						,'nahrada'=>array(
							'typ'=>
							,'hodnota'=>
						)
						,'omezeni'=>array(
							'operator'=> = ; >=... LIKE...
							,'typ'=> jako u nahrady
							,'hodnota'=>
						)
						,'nesynchronizovat'=>
			*/
		/*protected function zmenNazevDatabaze($databaze){
			if($this->proverNazev($databaze,true)){
				$this->nazevDatabaze = $databaze;
				if(isset($this->plnyNazevTabulky)){
					$this->naplnPlnyNazevTabulky();//musime reflektovat zmenu i v plnem nazvu tabulky
				}
			}
		}
		
		protected function zmenNazevTabulky($tabulka){
			if($this->proverNazev($tabulka,true)){
				$this->nazevTabulky = $tabulka;
				if(isset($this->plnyNazevTabulky)){
					$this->naplnPlnyNazevTabulky();//musime reflektovat zmenu i v plnem nazvu tabulky
				}
			}
		}

		protected function zmenNazevPripojeni($pripojeni){//zmenit nazev pripojeni neznamena prepojit se!
			if($this->proverNazev($pripojeni,true)){
				$this->nazevPripojeni = $pripojeni;
			}
		}*/
		
		protected function naplnZdroj(){
			if(!isset($this->zdroj)){
				$this->naplnTabulka();
				$this->naplnPlnyNazevTabulky();
				if(isset($this->tabulka['zdroj'])){
					if(is_object($this->tabulka['zdroj'])){//pokud jsme dostali zdroj ve forme tridy
						if(!is_subclass_of($this->tabulka['zdroj'],__CLASS__)){//pokud zdroj neni potomkem teto tridy
							trigger_error("Chybná třída zdroje (".get_class($this->tabulka['zdroj']).") pro tabulku $this->plnyNazevTabulky. Vyžadujeme potomka třídy ".__CLASS__.".Nelze pokračovat.",E_USER_ERROR);
						}
					}else{
						if(!($this->tabulka['zdroj'] === $this->plnyNazevTabulky)){//pokud jsme dostali nazev zdroje, ale ten se neshoduje s plnym nazvem tabulky
							trigger_error("Uvedený zdroj ".(string)$this->tabulka['zdroj']." pro tabulku $this->plnyNazevTabulky se neshoduje s plným názvem tabulky $this->plnyNazevTabulky. Namísto uvedeného zdroje bude použit plný název tabulky. Uvedený zdroj doporučujeme vymazat.",E_USER_WARNING);
							$this->tabulka['zdroj'] = &$this->plnyNazevTabulky;
						}
					}
					$this->zdroj = &$this->tabulka['zdroj'];//zdroj je nyni budto nazev tabulky, nebo objekt Struktura, ktery v sobe nese komplikovanejsi tabulkovou sestavu, kterou rozklicujeme az ve tride Synchronizace
				}else{
					$this->zdroj = &$this->plnyNazevTabulky;
				}
			}
		}

		protected function naplnDotazyZdroju(){//pokud si synchronizace zavola dotazy zdroju, naplnime je touto funkci (chceme-li je naplnit, prepiseme tuto funkci v dcerine tride
			$this->dotazyZdroju = array();
		}

		protected function proverNazev($nazev, $ukonciPriChybe = false){
			if(is_string($nazev)){
				if(!empty($nazev)){//ani prazdny retezec, ani nula
					return true;
				}else{
					if($ukonciPriChybe){
						trigger_error("Dodaný název je prázdný!",E_USER_WARNING);
						exit;
					}
				}
			}else{
				if($ukonciPriChybe){
					trigger_error("Dodaný název není text ale ".gettype($nazev)."!",E_USER_WARNING);
					exit;
				}
			}
			return false;//dostali jsme se bez preruseni az sem, tedy nazev je v chybnem formatu, ale my nechteli kvuli tomu ukoncovat program, vratime false
		}
	
		protected function naplnZajistiUloziste(){
			if(!isset($this->zajistiUloziste)){
				$this->zajistiUloziste = self::ZAJISTI_ULOZISTE;
			}
		}
		
		protected function naplnZajistiDatabazi(){
			$this->naplnZajistiUloziste();
			if(!isset($this->zajistiDatabazi)){
				if($this->zajistiUloziste){//zajisti uloziste ma prednost
					$this->zajistiDatabazi = self::ZAJISTI_ULOZISTE;
				}else{
					$this->zajistiDatabazi = $this->zajistiUloziste;
				}
			}
		}
		
		protected function naplnZajistiTabulku(){
			if(!isset($this->zajistiTabulku)){
				if(!isset($this->tabulka['zajistiTabulku'])){
					$this->zajistiTabulku = self::ZAJISTI_ULOZISTE;
				}else{
					$this->zajistiTabulku = (bool)$this->tabulka['zajistiTabulku'];
				}
				if(!$this->zajistiUloziste){//zajisti uloziste ma prednost
					$this->zajistiTabulku = $this->zajistiUloziste;
				}
				$this->tabulka['zajistiTabulku'] = &$this->zajistiTabulku;
			}
		}

		protected function naplnOmezeni(){
			if(!isset($this->omezeni)){
				$this->naplnStrukturaTabulky();
				$this->omezeni = array();
				foreach($this->strukturaTabulky as $pozicePolozky=>$polozka){
					if($polozka['omezeni']){
						$this->omezeni[$pozicePolozky] = &$polozka['omezeni'];
						$this->omezeni[$pozicePolozky]['nazev'] = &$polozka['nazev'];
					}
				}
			}
		}

		protected function rozsirOmezeni($seznamOmezeni){//funkce je protected, abychom si vynutili jeji volani jen z podtrid - pro udrzeni prehlednosti kodu
			if($seznamOmezeni !== false){
				foreach($seznamOmezeni as $identifikaceSloupce=>$strukturaOmezeni){
					$identifikaceSloupce = $this->dejNazevSloupceKlicovanehoPoradim($identifikaceSloupce);
					//jelikoz strukturaTabulky je referenci na polozky v tabulka['struktura'], tak zmenou v tabulka['struktura'] zmenime i obsah strukturaTabulky
					if($this->tabulka['struktura'][key($identifikaceSloupce)]['omezeni']){
						trigger_error("Více omezení jedné položky není zatím podporováno!",E_USER_WARNING);
						exit;
					}else{
						$this->tabulka['struktura'][key($identifikaceSloupce)]['omezeni'] = $this->dejProvereneOmezeni($strukturaOmezeni);
					}
				}
			}
		}

		protected function naplnCharacterSet(){
			if(!isset($this->characterSet)){
				if(!isset($this->tabulka['characterSet'])){
					$this->characterSet = PraceMysql::CHARACTER_SET;
					$this->tabulka['characterSet'] = $this->characterSet;
				}else{
					$this->characterSet = $this->tabulka['characterSet'];
				}
			}
		}
		
		protected function naplnEngine(){//funkce je volana pouze v pripade, ze engine dosud neni nastaven - pokud tedy chceme zmenit engine na odlisny od tohoto bezneho, musime jakkoli nastavit engine pred jeho potrebou
			if(!isset($this->engine)){
				if($this->typSql == PraceSql::MYSQL){			
					$this->engine = PraceMysql::ENGINE;
				}else{//pokud se ptame na engine a pritom nejsme v MySQL, je neco spatne a engine zustava null
					trigger_error("u zpracovávaného typu SQL ($this->typSql) neumíme nastavit ENGINE!",E_USER_NOTICE);
				}
			}
		}

		protected function naplnPozicePosledniZmeny(){//pozice posledni zmeny jsou dulezite pro zaznamenavani casu nejnovejsiho zaznamu
			if(!isset($this->pozicePosledniZmeny)){
				if(!isset($this->tabulka['pozicePosledniZmeny'])){//pokud neni pozice sloupce s posledni zmenou primo udana, zkusime pouzit posledni dostupny sloupec
					$this->naplnStrukturaTabulky();
					$indexPosledniPolozky = max(array_keys($this->strukturaTabulky));//index posledniho sloupce
					if(stripos($this->tabulka['struktura'][$indexPosledniPolozky]['datovyFormat'],'DATETIME') !== false){//pokud jde o cas a datum, muzeme pouzit posledni sloupec jako zdroj udaju o case posledni upravy radku
						$this->pozicePosledniZmeny = array($this->dejNazevSloupceKlicovanehoPoradim($indexPosledniPolozky));//ukladame nazev sloupce, klicovaneho jeho pozici
					}
				}else{//pozice posledni zmeny byla primo zadana(at uz poradim sloupce nebo jeho nazvem) - doplnime ji na plny format
					$this->pozicePosledniZmeny = array();
					foreach((array)$this->tabulka['pozicePosledniZmeny'] as $pozicePosledniZmeny){
						$this->pozicePosledniZmeny[] = $this->dejNazevSloupceKlicovanehoPoradim($pozicePosledniZmeny);
					}
				}
				$this->sloupcePosledniZmeny = array();
				foreach($this->pozicePosledniZmeny as &$pozicePosledniZmeny){
					$this->sloupcePosledniZmeny[] = current($pozicePosledniZmeny);
					$pozicePosledniZmeny = key($pozicePosledniZmeny);
				}
				$this->tabulka['pozicePosledniZmeny'] = &$this->pozicePosledniZmeny;
			}
		}
		
		protected function naplnSloupcePosledniZmeny(){
			if(!isset($this->sloupcePosledniZmeny)){
				$this->naplnPozicePosledniZmeny();
			}
		}
		
		protected function naplnPlnyNazevTabulky(){
			if(!isset($this->plnyNazevTabulky)){
				$this->naplnNazevTabulky();
				$this->plnyNazevTabulky = PraceSql::udelejPlnyNazevTabulky($this->nazevDatabaze,$this->nazevTabulky,$this->typSql,'','',true);
			}
		}
		
		protected function naplnRazeni(){
			$this->napln('razeni');
		}
		
		protected function naplnSeskupeni(){
			$this->napln('seskupeni');
		}
		
		public function dejPolozkyZdroje($nahradyZvlast = false){//ocekavame nazvy vsech polozek, ktere chceme ze zdroje nacitat
			$polozky = array();
			foreach($this->strukturaTabulky as $poradiPolozky=>$polozka){
				if(!isset($polozka['nesynchronizovat']) or !$polozka['nesynchronizovat']){
					$nazev = PraceSql::ohranic($polozka['nazev'],$this->typSql);
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
						$nahrada = "$nahrada AS ".PraceSql::ohranic($polozka['nazev'],$this->typSql);
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
			}
			return $polozky;
		}
		
		private function napln($nazevPlnene){
			if(!isset($this->{$nazevPlnene})){
				if(isset($this->tabulka[$nazevPlnene])){
					$this->{$nazevPlnene} = array();
					foreach((array)$this->tabulka[$nazevPlnene] as $poradiSloupce=>$sloupec){
						if(!is_array($sloupec)){//jde-li jen o jednu hodnotu, nese sloupec pouze identifikaci sloupce a my mu pripradime ostatni parametry podle bezneho nastaveni
							$sloupec = $this->dejNazevSloupceKlicovanehoPoradim($sloupec);//ziskame zaruceny nazev sloupce
							$sloupec = array(//upravime informaci o razeni sloupce na pozadovanou strukturu
								'nazev'=>current($sloupec)
							);
							if($nazevPlnene == 'razeni'){
								$sloupec['razeni'] = self::RAZENI;
							}elseif($nazevPlnene == 'seskupeni'){
								$sloupec['razeni'] = self::RAZENI_SESKUPENI;
							}
						}
						if(!empty($sloupec)){//obsahuje-li sloupec nejake udaje
							switch($nazevPlnene){
								case 'seskupeni':
									$struktura = 'seskupení';
									break;
								case 'razeni':
									$struktura = 'řazení';
									break;
								default:
									$struktura = false;
							}
							foreach($sloupec as $druhUdajeOSloupci=>&$udajOSloupci){//projdeme strukturu sloupce k razeni (udaj o sloupci je referencovan, abychom mohli snadno upravovat jeho obsah)
								if(is_string($druhUdajeOSloupci)){
									$druhUdajeOSloupci = strtolower($druhUdajeOSloupci);//index pro jistotu srazime na mala pismena
								}
								switch($druhUdajeOSloupci){
									case 'nazev':
										$udajOSloupci = $this->dejNazevSloupceKlicovanehoPoradim($udajOSloupci);//ziskame zaruceny nazev sloupce
										$udajOSloupci = current($udajOSloupci);
										break;
									case 'razeni':
										$udajOSloupci = strtoupper($udajOSloupci);
										switch($udajOSloupci){
											case self::RAZENI_SESTUPNE:
												break;
											case self::RAZENI_VZESTUPNE:
												break;
											default:
												if($nazevPlnene == 'razeni'){
													$udajOSloupci = self::RAZENI;
												}elseif($nazevPlnene == 'seskupeni'){
													$udajOSloupci = self::RAZENI_SESKUPENI;
												}
										}
										break;
									default:
										trigger_error("Neznámý prvek '$druhUdajeOSloupci' ve struktuře sloupce".($struktura ? "k $struktura" : ''),E_USER_WARNING);
								}
							}
							if(!isset($sloupec['nazev'])){
								trigger_error("U položky pro $struktura v pořadí $$poradiSloupce není uveden název! Nelze pokračovat",E_USER_ERROR);
								exit;
							}
							$this->{$nazevPlnene}[$poradiSloupce] = $sloupec;
						}
					}
				}else{
					$this->{$nazevPlnene} = false;
				}
			}
		}

		protected function naplnStrukturaTabulky(){
			if(!isset($this->strukturaTabulky)){
				$this->strukturaTabulky = $this->tabulka['struktura'];
				ksort($this->strukturaTabulky);//seradime polozky vzestupne podle indexu, abychom nemuseli nadale hlidat poradi polozek
				foreach($this->strukturaTabulky as $index=>&$polozka){
					if(!is_array($polozka)){//dostali jsme orezanou verzi polozky, doplnime ji na plnou strukturu
						$polozka = array('nazev'=>(string)$polozka);
					}
					$nepodporovaneNastaveni = array();
					foreach($polozka as $nazevNastaveni=>&$nastaveni){
						switch($nazevNastaveni){//slouzi k uprave a kontrole nastaveni danych uzivatelem, vcetne kontroly neznameho nastaveni
							case 'nazev':
								break;
							case 'datovyFormat':
								break;
							case 'rozmerDatovehoFormatu':
								break;
							case 'dodatekDatovehoFormatu':
								break;
							case 'index':
								break;
							case 'nahrada'://StrukturaSql neproveruje typy ani hodnotu nahrady, pouze obecnou strukturu
								if(!is_array($nastaveni)){
									$nastaveni = array(
										'hodnota' => $nastaveni
										,'typ' => self::TYP_NAHRADY
									);
								}else{
									$nepodporovaneNastaveniNahrady = array();
									foreach(array_keys($nastaveni) as $nazevNastaveniNahrady){//jestlize chceme nahradu pro zdroj, slozenou z pole, pak musime primo uvest strukturu nahrady, od typu po hodnotu - typ nebude automaticky dodan, respektive jeho neuvedeni je povazovano za vaznou chybu
										switch($nazevNastaveniNahrady){
											case 'hodnota':
												break;
											case 'typ':
												break;
											default:
												$nepodporovaneNastaveniNahrady[] = $nazevNastaveniNahrady;
												unset($nastaveni[$nazevNastaveniNahrady]);
										}
										if(sizeof($nepodporovaneNastaveniNahrady) > 0){
											trigger_error("Některá požadovaná nastavení náhrady (".implode($nepodporovaneNastaveniNahrady).") struktury položky '".$polozka['nazev']."' nejsou podporována a budou ignorována",E_USER_WARNING);
										}
									}
									if(!isset($nastaveni['hodnota'])){
										trigger_error("Náhrada ve struktuře ".get_class($this)." pro položku '".$polozka['nazev']."' neobsahuje hodnotu náhrady!",E_USER_ERROR);
										exit;
									}
									if(!isset($nastaveni['typ'])){
										$nastaveni['typ'] = self::TYP_NAHRADY;
									}
								}
								break;
							case 'omezeni':
								$nastaveni['omezeni'] = $this->dejProvereneOmezeni($nastaveni);
								break;
							case 'nesynchronizovat':
								break;
							default:
								$nepodporovaneNastaveni[] = $nazevNastaveni;
								unset($polozka[$nazevNastaveni]);
						}
					}
					if(!isset($polozka['nazev'])){
						trigger_error("U položky v pořadí $index není uveden název! Nelze pokračovat",E_USER_ERROR);
						exit;
					}
					if(!isset($polozka['datovyFormat'])){
						$polozka['datovyFormat'] = self::DATOVY_FORMAT;
					}
					if(!isset($polozka['rozmerDatovehoFormatu'])){
						$polozka['rozmerDatovehoFormatu'] = self::ROZMER_DATOVEHO_FORMATU;
					}
					if(!isset($polozka['dodatekDatovehoFormatu'])){
						$polozka['dodatekDatovehoFormatu'] = self::DODATEK_DATOVEHO_FORMATU;
					}
					if(!isset($polozka['index'])){
						$polozka['index'] = self::INDEX;
					}
					if(!isset($polozka['nahrada'])){
						$polozka['nahrada'] = self::NAHRADA;
					}
					if(!isset($polozka['omezeni'])){
						$polozka['omezeni'] = self::OMEZENI;
					}
					if(!isset($polozka['nesynchronizovat'])){
						$polozka['nesynchronizovat'] = self::NESYNCHRONIZOVAT;
					}
					if(sizeof($nepodporovaneNastaveni) > 0){
						trigger_error("Některá požadovaná nastavení (".implode($nepodporovaneNastaveni).") struktury položky '".$polozka['nazev']."' nejsou podporována a budou ignorována",E_USER_WARNING);
					}
				}
				$this->tabulka['struktura'] = &$this->strukturaTabulky;
			}
		}
		
		private function dejProvereneOmezeni($omezeni){
			if(!is_array($omezeni)){
				$omezeni = array(
					'operator' => self::OPERATOR_OMEZENI
					,'typ' => self::TYP_OMEZENI
					,'hodnota' => $omezeni
				);
			}else{
				if(!isset($omezeni['hodnota'])){
					trigger_error("U prověřovaného omezení nelze určit hodnotu omezení!",E_USER_ERROR);
					exit;
				}else{
					if(!isset($omezeni['operator'])){	
						$omezeni['operator'] = self::OPERATOR_OMEZENI;
					}
					if(!isset($omezeni['typ'])){	
						$omezeni['typ'] = self::TYP_OMEZENI;
					}
				}
			}
			return $omezeni;
		}
		
		protected function naplnIndexyPresSloupce(){
			if(!isset($this->indexyPresSloupce)){
				$this->indexyPresSloupce = array();
				if(isset($this->tabulka['indexyPresSloupce'])){//pokud vubec nejake indexy pres sloupce mame
					$this->pridejPozadavekNaIndexPresSloupce($this->tabulka['indexyPresSloupce']);
				}
				$this->tabulka['indexyPresSloupce'] = &$this->indexyPresSloupce;
			}
		}
		
		private function pridejPozadavekNaIndexPresSloupce($pozadavkyNaIndexPresSloupce){
			$ocekavaneIndexy = array(//toto jsou indexy, ktere urcuji dno struktury indexu pres sloupce a odkazuji (mely by) primo na poradi, popripade nazev sloupcu, ze kterych je index slozen
				'nepovinne'=>array('index')
				,'povinne'=>array('sloupce')
			);
			$pocetOcekavanychIndexu = sizeof($ocekavaneIndexy['povinne']);//pro kontrolni ucely si zapamatujeme pocet indexu, ze kterych ma byt slozeno dno seznamu indexu pres sloupce
			$dockaneIndexy = array(//sem si ulozime indexy dna, ktere jsme skutecne nasli (melo by jich byt budto 0 -pokud nejsme jeste u dna - nebo presne pocet ocekavanych indexu, vse sotatni je chyba struktury)
				'nepovinne'=>array()
				,'povinne'=>array()
			);
			$necekaneIndexy = array();
			foreach(array_keys($pozadavkyNaIndexPresSloupce) as $indexKProvereni){//projdeme indexy nejvyssi urovne z dodaneho seznamu a budeme hledat ocekavane indexy dna
				if(!in_array($indexKProvereni,(array)$ocekavaneIndexy['povinne'],true)){//tenhle index jsme necekali, jde zrejme o hlubsi pole - bude vice indexu pres sloupce
					if(!in_array($indexKProvereni,(array)$ocekavaneIndexy['nepovinne'],true)){
						$necekaneIndexy[] = $indexKProvereni;//odlozime si index, pod kterym je zatim neznama struktura pozadavku na index pres sloupce
					}else{
						unset($ocekavaneIndexy[array_search($indexKProvereni,$ocekavaneIndexy)]);
						$dockaneIndexy['nepovinne'][] = $indexKProvereni;
					}
				}else{//index byl cekany, odstranime ho ze seznamu ocekavanych (nemel by se opakovat)
					unset($ocekavaneIndexy[array_search($indexKProvereni,$ocekavaneIndexy)]);
					$dockaneIndexy['povinne'][] = $indexKProvereni;
				}
			}
				if(sizeof($dockaneIndexy['povinne']) === $pocetOcekavanychIndexu){//ve strukture byl pozadavek prave na jeden index pres sloupce a to se spravnymi indexy pozadovanych informaci pro jeho konstrukci
					$indexPresSloupce = array();//pripravime bunku pro prave nalezeny index pres sloupce
					foreach($dockaneIndexy['povinne'] as $dockanyIndex){
						if(($dockanyIndex != 'index') or !is_array($pozadavkyNaIndexPresSloupce[$dockanyIndex])){//pokud index pole odkazuje na typ sql indexu, musi jit o konkretni hodnotu, nikoliv pole - to by byl chybovy stav
							$indexPresSloupce[$dockanyIndex] = $pozadavkyNaIndexPresSloupce[$dockanyIndex];
						}else{
							trigger_error('Struktura indexu přes sloupce je chybná! Nelze pokračovat',E_USER_ERROR);
							exit;
						}
					}
					if(!isset($pozadavkyNaIndexPresSloupce['index'])){//jestlize uzivatel z lenosti nezadal typ indexu
						$pozadavkyNaIndexPresSloupce['index'] = self::TYP_INDEXU;//nastavime zakladni
						$dockaneIndexy['nepovinne'][] = 'index';//a pridame jeho druh do seznamu ke zpracovani
					}
					foreach($dockaneIndexy['nepovinne'] as $dockanyIndex){
						if(($dockanyIndex != 'index') or !is_array($pozadavkyNaIndexPresSloupce[$dockanyIndex])){//pokud index pole odkazuje na typ sql indexu, musi jit o konkretni hodnotu, nikoliv pole - to by byl chybovy stav
							$indexPresSloupce[$dockanyIndex] = $pozadavkyNaIndexPresSloupce[$dockanyIndex];
						}else{
							trigger_error('Struktura indexu přes sloupce je chybná! Nelze pokračovat',E_USER_ERROR);
							exit;
						}
					}
					$this->indexyPresSloupce[] = $indexPresSloupce;
				}elseif(sizeof($dockaneIndexy['povinne']) > 0){//nejakych indexu, ktere jsme cekali, jsme se i dockali, ale bylo jich jiny pocet, nez je treba
					trigger_error('Struktura indexu přes sloupce je chybná! Nelze pokračovat',E_USER_ERROR);
					exit;
				}
				if(sizeof($necekaneIndexy) > 0){//mame jine, nez zakladni indexy, proverime je
					foreach($necekaneIndexy as $necekanyIndex){
						$this->pridejPozadavekNaIndexPresSloupce($pozadavkyNaIndexPresSloupce[$necekanyIndex]);//rekurze v pripade, kdy struktura pozadavku byla hlubsi
					}
				}
				foreach($this->indexyPresSloupce as &$indexPresSloupce){//projdeme indexy pres sloupce a dokoncime jejich strukturu
					$preklicovanyIndexPresSloupce = array();
					foreach($indexPresSloupce['sloupce'] as $sloupec){//pro kazdou polozku, ze ktere je index slozeny
						$sloupec = $this->dejNazevSloupceKlicovanehoPoradim($sloupec);//vraci nazev sloupce, klicovany jeho poradim
						$preklicovanyIndexPresSloupce[key($sloupec)] = current($sloupec);//protoze poradi sloupce muze kolidovat s poradim indexu pres sloupce, ktery jsme jeste nezpracovali, musime si odkladat nalezene sloupce stranou
					}
					$indexPresSloupce['sloupce'] = $preklicovanyIndexPresSloupce;//finalni verzi seznamu sloupcu, tvoricich index, si ulozime
				}
		}
		
		public function dejNazevSloupceKlicovanehoPoradim($udajOSloupci,$indexyRovneRealnymPolozkam = false){
			$nazevSloupceKlicovanehoPoradim = false;
			if(!is_array($udajOSloupci)){
				if(!isset($this->strukturaTabulky)){
					$this->naplnStrukturaTabulky();
				}
				if(isset($this->strukturaTabulky[$udajOSloupci])){
					return array($udajOSloupci => $this->strukturaTabulky[$udajOSloupci]['nazev']);
				}else{
					foreach($this->strukturaTabulky as $poradi=>$polozka){
						if($polozka['nazev'] == $udajOSloupci){
							if($nazevSloupceKlicovanehoPoradim === false){
								$nazevSloupceKlicovanehoPoradim = array($poradi => $polozka['nazev']);
							}else{
								trigger_error("Hledaný sloupec ve struktuře ".get_class($this)." s identifikací $udajOSloupci existuje vícekrát! Jednoznačná idntifikace není bez číselného určení možná.",E_USER_ERROR);
							}
						}
					}
					if($nazevSloupceKlicovanehoPoradim === false){
						trigger_error("Hledaný sloupec ve struktuře ".get_class($this)." s identifikací $udajOSloupci neexistuje!",E_USER_ERROR);
						exit;
					}
				}
			}else{
				trigger_error("Údaj pro identifikaci sloupce nemůže být pole!",E_USER_ERROR);
				exit;
			}
			$this->naplnNazvySloupcuProSynchronizaci();
			if($indexyRovneRealnymPolozkam){//nechceme index polozky podle indexu zapsanem ve strukture, ale skutecne poradi polozky, pod jakym bude ziskana ci nahrana - coz se muze od zapsane struktury lisit podle odchylky udanych indexu od reality a podle priznaku nesynchronizovat
				$realnePoradi = array_search(current($nazevSloupceKlicovanehoPoradim),$this->nazvySloupcuProSynchronizaci);//najdeme index/poradi polozky v seznamu polozek razenych dle reality
				if($realnePoradi !== false){
					$nazevSloupceKlicovanehoPoradim = array($realnePoradi=>current($nazevSloupceKlicovanehoPoradim));
				}else{
					trigger_error("Požadovaná položka neexistuje v reálné skupině položek! Pravděpodobně je označena jako 'nesynchronizovat'",E_USER_ERROR);
					exit;
				}
			}
			return $nazevSloupceKlicovanehoPoradim;
		}
		
		protected function naplnNazevTabulky(){
			if(!isset($this->nazevTabulky)){
				$this->nazevTabulky = &$this->tabulka['nazev'];
			}
		}
		
		protected function naplnNazvySloupcu(){
			if(!isset($this->nazvySloupcu)){
				$this->nazvySloupcu = array();
				foreach($this->tabulka['struktura'] as $index=>$polozka){
					$this->nazvySloupcu[$index] = $polozka['nazev'];
				}
			}
		}
		
		protected function naplnNazvySloupcuProSynchronizaci(){//nazvy sloupcu pro synchronizaci nedrzi indexy ze struktury, ale indexy shodne s poradim realnych polozek, ktere budeme nacitat/ukladat
			$this->naplnStrukturaTabulky();
			if(!isset($this->nazvySloupcuProSynchronizaci)){
				$this->nazvySloupcuProSynchronizaci = array();
				foreach($this->strukturaTabulky as $polozka){
					if(!$polozka['nesynchronizovat']){
						$this->nazvySloupcuProSynchronizaci[] = $polozka['nazev'];
					}
				}
			}
		}
		
		protected function naplnVztahKlicu(){
			if(isset($this->tabulka['vztahKlicu'])){//mame specielni vztah klicu
				switch(strtoupper($this->tabulka['vztahKlicu'])){
					case self::KLICE_VZTAH_AND:
						break;
					case 'A':
						$this->tabulka['vztahKlicu'] = self::KLICE_VZTAH_AND;
						break;
					case self::KLICE_VZTAH_OR:
						break;
					case 'NEBO':
						$this->tabulka['vztahKlicu'] = self::KLICE_VZTAH_OR;
						break;
					default:
						trigger_error("Neznámý vztah klíčů tabulky (".$this->tabulka['klice']['vztah']."), nelze pokračovat",E_USER_ERROR);
						exit;
				}
				$this->vztahKlicu = &$this->tabulka['vztahKlicu'];
			}else{//standardne jsou vsechny polozky, uvedene v klicich, soucasti klice a tvori klic pouze jako celek
				$this->vztahKlicu = self::KLICE_VZTAH_AND;
				$this->tabulka['vztahKlicu'] = &$this->vztahKlicu;
			}
		}
		
		protected function naplnKlice(){
			if(!isset($this->klice)){
				if(isset($this->tabulka['klice'])){
					$this->klice = array();
					$this->tabulka['klice'] = (array)$this->tabulka['klice'];
					foreach($this->tabulka['klice'] as $infoOKlici){
						$infoOKlici = $this->dejNazevSloupceKlicovanehoPoradim($infoOKlici,true);
						$this->klice[key($infoOKlici)] = current($infoOKlici);
					}
				}else{//pozice klicu nebyly zadany, projdeme tedy strukturu a zkusime najit klice schovane v typech indexu ci dodatku datoveho formatu
					foreach($this->tabulka['struktura'] as $pozicePolozky=>$polozka){
						if((stripos($polozka['dodatekDatovehoFormatu'],'PRIMARY') !== false) or (stripos($polozka['dodatekDatovehoFormatu'],'UNIQUE') !== false)){
							$this->tabulka['klice'] = array($pozicePolozky);
							break;//staci nam jen jeden klic, pripadne dalsi pro pozice klicu ignorujeme
						}
					}
					if(!isset($this->tabulka['klice'])){//stale nemame pozice klicu, zkusime posledni moznost - hledani klicu v indexech pres sloupce
						$this->naplnIndexyPresSloupce();
						if(sizeof($this->indexyPresSloupce) > 0){//nejake indexy pres sloupce mame, zkusime vnich najit unique index, popripade primary key
							foreach($this->indexyPresSloupce as $indexPresSloupce){
								if(stripos($indexPresSloupce['index'],'PRIMARY') !== false){
									$this->tabulka['klice'] = array_keys($indexPresSloupce['sloupce']);
									break;//staci nam jen jeden klic, pripadne dalsi pro pozice klicu ignorujeme
								}
							}
						}
					}
					if(!isset($this->tabulka['klice'])){
						trigger_error("V tabulce nejsou určeny žádné klíče! Nelze pokračovat",E_USER_ERROR);//ve strukture zdroje klice byt nemusi, ale pokud jsme zavolali funkci naplnKlice, pak klice vyzadujeme a jde tedy o strukturu cile
						exit;
					}else{
						$this->naplnKlice();
					}
				}
			}
		}

		protected function naplnOmezeniKlicu(){
			if(!isset($this->omezeniKlicu)){
				$this->naplnKlice();
				$this->naplnVztahKlicu();
				$this->omezeniKlicu = '';
				switch($this->vztahKlicu){
					case self::KLICE_VZTAH_AND:
						$spojka = 'AND';
						break;
					case self::KLICE_VZTAH_OR:
						$spojka = 'OR';
						break;
					default:
						trigger_error("Neznámý vztah klíčů (".$this->klice['vztah']."), nelze pokračovat",E_USER_ERROR);
						exit;
				}
				$spojku = false;
				foreach($this->klice as $index=>$klic){//pro kazdy klic tabulky
					$hodnota = '?';
					if(isset($this->strukturaTabulky[$index]['nahrada']) and !empty($this->strukturaTabulky[$index]['nahrada'])){//jestlize se klice tyka nejaky druh nahrady
						switch(strtolower($this->strukturaTabulky[$index]['nahrada']['typ'])){//proverime typ nahrady a pripadne upravime ukladani podle nej
							case 'udaj'://holy udaj, respektive konstanta - neni nacitana ze zdroje, nybrz pridavana do dotazu zajistujicim ulozeni (napr. ?,?,'Main',?)
								$hodnota = "'".$this->strukturaTabulky[$index]['nahrada']['hodnota']."'";//obalime hodnotu, kterou za chvili vsadime do dotazu
								break;
							case 'sql'://kod ve formatu SQL - pouzijeme ho stejne jako udaj, krome ohraniceni, ktere je u SQL kodu nesmyslne
								$hodnota = $this->strukturaTabulky[$index]['nahrada']['hodnota'];
								break;
							default:
								$chyba = "Tento typ náhrady '".$this->strukturaTabulky[$index]['nahrada']['typ']."' neznám!";
								trigger_error($chyba,E_USER_ERROR);
						}
					}
					$this->omezeniKlicu .= ($spojku ? "\n $spojka" : '').' '.PraceSql::ohranic($klic,$this->typSql)."=$hodnota";
					$spojku = true;
				}
			}
		}
		
	}
?>