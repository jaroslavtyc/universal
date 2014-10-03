<?php
namespace universal;

abstract class PrihlaseniHtml extends BaseClass {

	const ULOZISTE_ZAKLAD = 'prihlaseni_html_';
	const MAXIMALNI_CAS_NECINNOSTI = 120;//sekund

	protected $sul;
	protected $HtmlChyby;

	protected $Pripojeni;
	protected $PraceSWebem;

	protected static $nazev;
	protected static $doklady;//odkaz na SESSION, ve ktere ukladame informace o prihlaseni

	protected $maximalniCasNecinnosti = self::MAXIMALNI_CAS_NECINNOSTI;

	public function __construct($nazevPripojeni,$databaze,$sul){
		$this->Pripojeni = new $nazevPripojeni();
		$this->Pripojeni->zmenDatabazi($databaze);
		self::$nazev = get_class($this);
		$this->HtmlChyby = new HtmlChyby(HtmlChyby::METODA_NAVRATU_GET);//spousti session
		$this->PraceSWebem = new PraceSWebem();
		if(is_string($sul) and (strlen($sul) > 1)){
			$this->sul = $sul;
		}else{
			trigger_error("Není čím solit!",E_USER_ERROR);
		}
		$this->pripravDoklady();
		$this->zajistiUloziste();
	}

	public function nastavMaximalniCasNecinnosti($maximalniCasNecinnosti){
		$this->maximalniCasNecinnosti = (int)$maximalniCasNecinnosti;
	}

	public function dejSeznamChyb($metodaNavratu = HtmlChyby::VSECHNY_METODY_NAVRATU){
		$seznam = false;
		if($this->HtmlChyby->existOldError()){
			$seznam = '<div class="chyby">';
			foreach($this->HtmlChyby->getErrors() as $metodaNavratu=>$chybyMetody){
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

	private function pripravDoklady(){
		if(isset($_SESSION[self::$nazev])){
			self::$doklady = &$_SESSION[self::$nazev];
		}
	}

	public function dejPrihlasovaciFormular($cil = '', $nadpis = false){//bezne kontrolujeme prihlaseni na te same strance, ze ktere vzesel formular na prihlaseni
		$formular =
			"<form action='$cil' method='post' id='prihlasovaciFormular'>
				<fieldset>";
		if($label !== false){
			$formular .= "
					<label>$nadpis</label>";
		}
		$formular .= "
					<input type='text' name='uzivatel-".self::$nazev."'/> Uživatel<br/>
					<input type='password' name='heslo-".self::$nazev."'/> Heslo<br/>
					<input type='submit' value='Přihlaš mě'/>
				</fieldset>
			</form>";
		return $formular;
	}

	public static function ukazPrihlasovaciFormular($cil = ''){
		echo(self::dejPrihlasovaciFormular($cil));
	}

	public function prihlaseniVPoradku(){//pokud v poradku nebude, presmeruje sam zpet na zacatek
		if(isset($_POST["odhlaseni-".self::$nazev])){
			$this->odhlasMe();
			return false;
		}else{
			if(!isset(self::$doklady['casPrihlaseni'])){
				$this->HtmlChyby->zapamatujChybu('chybí autentický záznam','Přihlášení');
			}elseif((self::$doklady['casPrihlaseni']+$this->maximalniCasNecinnosti) < time()){
				$this->HtmlChyby->zapamatujChybu('vypršel','Čas');
			}
			if($this->HtmlChyby->jeNovaChyba()){
				return false;
			}else{
				return true;
			}
		}
	}

	public function prihlasMe($cilUspechu,$cilNeuspechu = 'index.php',$navratPriNespustenemPrihlaseni = false){//zjisti, zda jsou udaje v POST a proveri je - jsou-li spravne, presmeruje dale, jinak vrati
		if(isset($_POST) and isset($_POST['uzivatel-'.self::$nazev]) and isset($_POST['heslo-'.self::$nazev])){//pokud uzivatel prisel z prihlasovaci stranky
			if($_POST['uzivatel-'.self::$nazev] == ''){//byl-li zadan uzivatel
				$this->HtmlChyby->zapamatujChybu('nevyplněno','Uživatelské jméno');
			}
			if($_POST['heslo-'.self::$nazev] == ''){//prazdne heslo neni povoleno
				$this->HtmlChyby->zapamatujChybu('nevyplněno','Heslo');
			}
			if(!$this->HtmlChyby->jeNovaChyba()){
				if($this->prihlas($_POST['uzivatel-'.self::$nazev],$_POST['heslo-'.self::$nazev])){
					unset($_POST['uzivatel-'.self::$nazev]);
					unset($_POST['heslo-'.self::$nazev]);
					$this->PraceSWebem->presmeruj($cilUspechu);
				}//pri neuspesnem prihlaseni jsou jiz chyby nasbirani metodou prihlas
			}
		}
		if($navratPriNespustenemPrihlaseni or $this->HtmlChyby->jeNovaChyba()){//bezne se nespusti presmerovani, pokud uzivatel dosud neodeslal prihlasovaci udaje
			$this->HtmlChyby->navratSChybami($cilNeuspechu);//nedoslo k presmerovani pri uspechu, vratime se na stranku pro neuspech
		}
	}

	public function prihlas($uzivatel,$heslo){
		if($this->overUzivatele($uzivatel,$heslo)){
			self::$doklady = array();
			if(isset($_SESSION)){
				$_SESSION[self::$nazev] = &self::$doklady;
				self::$doklady['casPrihlaseni'] = time();
				return true;
			}else{
				trigger_error('Chybí doklady',E_USER_NOTICE);
				return false;
			}
		}else{
			$this->HtmlChyby->zapamatujChybu('chybné','Přihlašovací údaje');
			return false;
		}
	}

	public function nazev(){
		return self::$nazev;
	}

	public function dejOdhlasovaciFormular($cil = ''){//'presmeruje' bezne opet na soucasnou stranku a zde po spusteni prihlaseniVPoradku vyhodnoti odhlaseni (mene pohodlny, ale fajnovejsi zpusob by bylo presmerovani na specializovanou odhlasovaci stranku, napr odhlaseni.php)
		$formular = "
			<style>.odhlasovaciFormular{text-align: right}</style>
			<form method='POST' action='$cil' class='odhlasovaciFormular' >
				<input type='submit' value='Odhlaš mě' name='odhlaseni-".self::$nazev."'>
			</form>";
		return $formular;
	}

	public function zobrazOdhlasovaciFormular($cil = ''){
		echo($this->dejOdhlasovaciFormular($cil));
	}

	public function odhlasMe($cilNavratu = 'index.php'){
		self::$doklady = null;//balime kufry
		$this->HtmlChyby->zapamatujChybu('v pořádku','Odhlášení');
		$this->HtmlChyby->navratSChybami($cilNavratu);
	}

	public function existOldError(){
		return $this->HtmlChyby->existOldError();
	}

	public function jeNovaChyba(){
		return $this->HtmlChyby->jeNovaChyba();
	}

	public function navratSChybami($cil = 'index.php'){
		if($this->HtmlChyby->jeNovaChyba()){
			$this->HtmlChyby->navratSChybami($cil);
		}else{
			return false;
		}
	}

	public function overUzivatele($uzivatel = null, $heslo = null){//pro inicializaci uzivatele vloz do tabulky do sloupce jmeno uzivatele zakryptovaneho podle this->sul, do hesla zakryptovane heslo podle this->sul a znovu zakryptovane podle libovbolne soli (po prvnim prihlaseni, bude sul v tabulce regenerovana)
		$uzivatel = crypt($uzivatel,$this->sul);//prihlasovaci jmeno je zakryptovany retezec uzivatelskeho jmena
		$heslo = crypt($heslo,$this->sul);//stejne tak heslo
		//echo("$uzivatel $heslo ".crypt($heslo,'/'));
		$slane = $this->dejSlane($uzivatel, $heslo);//podle klice jmeno-heslo ziskame posledni dil skladacky
		$overeni = (crypt($heslo,$slane) == $slane);//pokud kryptujeme retezec vysledkem, dostaneme vysledek - cimz overime ziskany kod
		return $overeni;//vracime vysledek porovnani kodu a zakrytpovaneho hesla podle tohoto kodu (true / false)
	}

	protected function zajistiUloziste(){
		$dotaz = "CREATE TABLE IF NOT EXISTS
			`".self::ULOZISTE_ZAKLAD.self::$nazev."` (
			`uzivatel` VARCHAR(100) PRIMARY KEY
			,`sul` VARCHAR(100) NOT NULL
			,`heslo` VARCHAR(100) NOT NULL)";
		$this->Pripojeni->provedDotaz($dotaz);
	}

	public function dejSlane($uzivatel, $heslo){//vratime dynamicky kryptovane heslo
		$uzivatel = $this->Pripojeni->predrad($uzivatel);
		$dotaz = "
			SELECT
				`heslo`
			FROM
				`".self::ULOZISTE_ZAKLAD.self::$nazev."`
			WHERE
				`uzivatel` = '$uzivatel'
		";
		$slane = UtilitkyProPole::orezPoleASmazPrazdno($this->Pripojeni->provedDotaz($dotaz));
		if(!empty($slane)){
			$this->osol($uzivatel, $heslo);//nove zakryptovani hesla
		}
		return $slane;
	}

	private function osol($uzivatel, $heslo){//zmeni sul a prekoduje heslo podle nove soli
		$sul = '';
		$delka = mt_rand(10,20);
		for($i = 0; $i < $delka; $i++){//vygenerujeme novou sul
			$sul .= bin2hex(chr(mt_rand(0,255)));
		}
		$slane = crypt($heslo,$sul);//osolime heslo novou soli
		$sul = $this->Pripojeni->predrad($sul);
		$slane = $this->Pripojeni->predrad($slane);
		$dotaz = "
			UPDATE
				`".self::ULOZISTE_ZAKLAD.self::$nazev."`
			SET
				`sul` = '$sul'
				,`heslo` = '$slane'
			WHERE
				`uzivatel` = '$uzivatel'
		";
		$this->Pripojeni->provedDotaz($dotaz);
	}
}