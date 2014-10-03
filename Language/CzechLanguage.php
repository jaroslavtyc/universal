<?php
namespace universal;
class CzechLanguage extends Language {

	const LANGUAGE_NAME = 'czech';
	const LANGUAGE_CODE = 'CZE';
	const LOCAL_LANGUAGE_NAME = 'čeština';

	/**
	* Initialization function of class
	*/
	public function __construct(){
		parent::__construct(self::LANGUAGE_NAME, self::LANGUAGE_CODE, self::LOCAL_LANGUAGE_NAME);
	}
	
}