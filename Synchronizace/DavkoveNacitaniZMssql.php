<?php
namespace universal;

require_once(dirname(__FILE__).'/DavkoveNacitaniZSql.php');

class DavkoveNacitaniZMssql extends DavkoveNacitaniZSql{

	private $polozky;
	private $zdrojeAOmezeni;
	private $razeno;
	private $ohranicPolozky;

	private $poziceDataACasu = array();
	private $nahrady = array();

	public function __construct($Pripojeni, $polozky, $zdrojeAOmezeni, $radkuNaDavku, $razeno, $ohranicPolozky = true){
		$this->polozky = $polozky;
		$this->zdrojeAOmezeni = $zdrojeAOmezeni;
		$this->razeno = (!is_array($razeno) ? array($razeno=>'ASC') : $razeno);//zajistime plny format pro 'razeno', pokud jsme dostali zjednodusenou verzi
		$this->ohranicPolozky = $ohranicPolozky;
		if(isset($this->polozky['datumACasNaPozici'])){//pokud jsme dostali seznam sloupcu, ktere jsou typu datum_a_cas, tak ziskame jejich indexy v seznamu sloupcu; pozn. pokud prenasime data z MS SQL opet na MS SQL, nebo nam ztrata milisekund nevadi, pak neni tato funkce uzitecna - vyradime ji prostym vynechanim zaznamu datumACasNaPozici
			$this->poziceDataACasu = UtilitkyProPole::dejKliceNaPozicich($polozky,$polozky['datumACasNaPozici'],false);
			unset($this->polozky['datumACasNaPozici']);//smazeme jiz nepotrebnou a v dalsim kroku rusivou informaci
		}
		if(isset($this->polozky['nahrady'])){
			$this->nahrady = $this->polozky['nahrady'];
			unset($this->polozky['nahrady']);
		}
		parent::__construct($Pripojeni, $this->sestavDotaz(), $radkuNaDavku);
	}

	private function sestavDotaz(){
		$dotaz = "
			SELECT
		";
		$dotaz .= $this->dejCile();
		$dotaz .= "
			FROM
				(SELECT
					ROW_NUMBER() OVER (
						ORDER BY ";
					$a = false;
					foreach($this->razeno as $razenoDle=>$razenoJak){
						$dotaz .=
						($a ? "\nAND " : '')."[$razenoDle] $razenoJak";
						$a = true;
					}
					$dotaz .= "
					) as [Seřazeno],
		";
		$dotaz .= $this->dejCile(true);
		$dotaz .= "
				FROM
					$this->zdrojeAOmezeni
				) as zakladni_omezeni
			WHERE
				[Seřazeno] > #Od# AND [Seřazeno] <= #Do#
		";
		return $dotaz;
	}

	private function dejCile($sNahradami = false){
		$cile = '';
		$carka = false;
		foreach($this->polozky as $indexPolozky=>$polozka){
			if($this->ohranicPolozky){
				$polozka = "[$polozka]";
			}
			if($sNahradami){
				if(in_array($indexPolozky,$this->nahrady)){
					$polozka = $this->nahrady[$indexPolozky];
				}
			}
			$cile .= ($carka ? ',' : '')."\n".(
				in_array($indexPolozky,$this->poziceDataACasu)
					? "CONVERT(VARCHAR,$polozka,21) as $polozka"
					: "$polozka"
			);//pokud je polozka oznacena jako datum_a_cas pro konverzi, prevedeme ji na retezec, ktery zachova informace o milisekundach
			$carka = true;
		}
		return $cile;
	}

	protected function dejUpravenyDotazNaLimit(){
		$dotaz = preg_replace('|#Od#|',$this->od,$this->dotaz,1);
		$dotaz = preg_replace('|#Do#|',($this->od+$this->radkuNaDavku),$dotaz,1);
		return $dotaz;
	}
}