<?php
namespace universal;

abstract class PripojeniSql extends BaseClass {

	const NOVE_PRIPOJENI = false;

	protected $server;
	protected $uzivatel;
	protected $heslo;
	protected $databaze;
	protected $port;
	protected $pripojeni;//jelikoz neni jiste, zda budeme pouzovat pripojeni pomoci procedur nebo trid, pouzijeme male pismeno na zacatku
	protected $trvaniDotazu;
	protected $posledniDotaz;
	protected $ovlivnenoRadku;
	protected $posledniId = false;

	const LOCALHOST = 'localhost';

	public function __construct($server, $uzivatel, $heslo, $databaze, $port){
		$this->makePropertiesReadable(
			'ovlivnenoRadku'
			,'posledniId'
		);

		$this->server = $server;
		$this->uzivatel = $uzivatel;
		$this->heslo = $heslo;
		$this->databaze = $databaze;
		$this->port = $port;

		$this->makePropertyReadable('databaze');
		if(empty($this->server)){
			$this->server = self::LOCALHOST;
		}
	}

	public function __destruct(){}

	abstract protected function pripojeni();

	abstract public function dejVerzi();

	public function databaze(){
		return $this->databaze;
	}

	protected function ulozCasZacatkuDotazu(){
		$this->trvaniDotazu = time();
	}

	protected function ulozTrvaniProvadeniDotazu(){
		$this->trvaniDotazu = (time() - $this->trvaniDotazu);
	}

	//abstract protected function pripojSe();

	abstract public function odpojSe();

	abstract public function zmenDatabazi($databaze);

	abstract public function ovlivnenoRadku();

	abstract protected function vyprazdniPametPoVysledku();

	public function predradZpetnaLomitka($data){
		return addcslashes($data, "\\");
	}

	public function provedDotazSOrezem($dotaz, $nahradPrazdnoNullem = false, $novePripojeni = self::NOVE_PRIPOJENI){
		return UtilitkyProPole::orezZbytecnaPoleDotazu($this->provedDotaz($dotaz, $novePripojeni), $nahradPrazdnoNullem);
	}

	abstract public function provedDotaz($dotaz);

	abstract public function vyprazdniTabulku($nazev);

	abstract public function vymazDataZTabulky($nazev);//rychlejsi, ale neresetuje Id

	abstract public function posledniId();

	abstract public function idPripojeni();
}