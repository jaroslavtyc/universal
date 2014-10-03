<?php
namespace universal;

abstract class Html extends BaseClass {

	const DOCTYPE_STRICT = "<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Strict//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd'>";
	const DOCTYPE = self::DOCTYPE_STRICT;
	const XMLNS = 'xmlns="http://www.w3.org/1999/xhtml"';
	const WINDOWS1250 = 'windows-1250';
	const WINDOWS_1250 = self::WINDOWS1250;
	const ZNAKOVA_SADA = self::WINDOWS1250;
	const UTF8 = 'UTF-8';
	const ISO_8859_2 = 'iso-8859-2';
	const JAZYK = self::JAZYK_CS;
	const JAZYK_CS = 'cs';
	const CS = self::JAZYK_CS;

	protected $znakovaSada;
	protected $jazyk;

	public function __construct($znakovaSada = self::ZNAKOVA_SADA,$jazyk = self::JAZYK){
		$this->nastavZnakovaSada($znakovaSada);
		$this->nastavJazyk($jazyk);
	}

	public function nastavZnakovaSada($znakovaSada){
		$this->znakovaSada = $znakovaSada;
	}

	public function nastavJazyk($jazyk){
		$this->jazyk = $jazyk;
	}
}