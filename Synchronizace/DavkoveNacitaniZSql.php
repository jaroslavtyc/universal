<?php
	require_once(dirname(__FILE__).'/../PraceSql/PraceSql.php');

	abstract class DavkoveNacitaniZSql extends PraceSql{

	protected $dotaz;
	protected $radkuNaDavku;

	protected $od;
	protected $jeCoCist;

		public function __construct($Pripojeni, $dotaz, $radkuNaDavku){
			parent::__construct();
			$this->pridejViditelnouVlatnost('jeCoCist');
			$this->Pripojeni = $Pripojeni;
			$this->dotaz = $dotaz;
			$this->radkuNaDavku = $radkuNaDavku;
			$this->od = 0;
			$this->jeCoCist = true;
		}
		
		public function dejDavku(){
			$davka = $this->Pripojeni->provedDotaz($this->dejUpravenyDotazNaLimit(),true);//pokazde si vyzadame nove spojeni se severem - abych om zbytecne nedrzeli spojeni, zatimco sjou data jinde zpracovavana
			$this->od += $this->radkuNaDavku;
			if(sizeof($davka) < $this->radkuNaDavku){//nacetli jsme mene radku, nez bylo omezeni - tzn. nebylo uz co cist
				$this->jeCoCist = false;
			}
			return $davka;
		}
		
		public function upravPocetRadkuNaDavku($novyPocetRadkuNaDavku){//pokud si rozmyslime velikost davky az po sestaveni instance, muzeme ji timto zmenit
			$this->radkuNaDavku = $novyPocetRadkuNaDavku;
		}
		
		//posun se hodi napriklad v pripade, ze nepouzijeme pri zpracovani vsechny radky a pri dalsim cteni budeme chtit tyto nezpracovane radky znovu
		public function posunZacatekDavky($posun){//posun muze byt i retezcem vyjadrene cislo, vcetne zaporneho
			$this->od += ($posun);
		}
		
		public function jeCoCist(){
			return $this->jeCoCist;
		}
		
		abstract protected function dejUpravenyDotazNaLimit();
	}
?>