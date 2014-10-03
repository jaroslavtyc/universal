<?php
namespace universal;

class Nazvy extends BaseClass {

protected $sloupcu = array();
protected $tabulek = array();
protected $popisuTabulek = array();
protected $databazi = array();

const CSV = 'csv';
const XML = 'xml';
const XLS = 'xls';
const HTM = 'htm';
const HTML = 'html';
const ID_TABULKY = 'id';
const ID_SLOUPCE = 'Id';

	public function __construct(){
		parent::__construct();
		$this->pridejViditelneVlastnosti(
			'sloupcu'
			,'tabulek'
			,'popisuTabulek'
			,'databazi'
		);
		//sice se zda, ze index je shodny s nazvem, pretvorenym pres 'udelej...', a taky zatim je, ale je to pojistka do budoucna, kdyby se nazvy menili, aby kod mohl zustat stejny
	}

	public function destruct(){
		parent::__destruct();
	}

	protected function pridejNazvySloupcu($nazvySloupcu){
		foreach($nazvySloupcu as $index=>$lidskyNazev){
			$this->sloupcu[$index] = PraceSql::udelejNazevSloupce($lidskyNazev);
		}
	}

	protected function pridejNazvyTabulek($nazvyTabulek){
		foreach($nazvyTabulek as $index=>$lidskyNazev){
			$this->tabulek[$index] = PraceSql::udelejNazevTabulky($lidskyNazev);
		}
	}

	protected function pridejPopisyTabulek($popisyTabulek){
		foreach($popisyTabulek as $index=>$lidskyNazev){
			$this->popisuTabulek[$index] = $lidskyNazev;
		}
	}

	protected function pridejNazvyDatabazi($nazvyDatabazi){
		foreach($nazvyDatabazi as $index=>$lidskyNazev){
			$this->databazi[$index] = PraceSql::udelejNazevDatabaze($lidskyNazev);
		}
	}
}